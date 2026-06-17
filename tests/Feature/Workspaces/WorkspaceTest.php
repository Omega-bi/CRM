<?php

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\User;
use Livewire\Livewire;

test('workspaces index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('workspaces.index'));

    $response->assertOk();
});

test('workspaces can be created', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::workspaces.index')
        ->set('name', 'Test Workspace')
        ->call('createWorkspace')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('workspaces', [
        'name' => 'Test Workspace',
        'is_personal' => false,
    ]);
});

test('workspace slug uses next available suffix', function () {
    $user = User::factory()->create();

    Workspace::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Workspace::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Workspace::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.index')
        ->set('name', 'Acme')
        ->call('createWorkspace')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('workspaces', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('workspace edit page can be rendered', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->get(route('workspaces.edit', $workspace));

    $response->assertOk();

    Livewire::test('pages::workspaces.edit', ['workspace' => $workspace])
        ->assertSeeHtml('data-test="workspace-name-input"')
        ->assertSeeHtml('data-test="invite-member-button"')
        ->assertSeeHtml('data-test="workspace-save-button"');
});

test('workspaces can be updated by owners', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create(['name' => 'Original Name']);

    $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.edit', ['workspace' => $workspace])
        ->set('workspaceName', 'Updated Name')
        ->call('updateWorkspace')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'name' => 'Updated Name',
    ]);
});

test('workspaces cannot be updated by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($member);

    Livewire::test('pages::workspaces.edit', ['workspace' => $workspace])
        ->set('workspaceName', 'Updated Name')
        ->call('updateWorkspace')
        ->assertForbidden();
});

test('workspaces can be deleted by owners', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $workspace])
        ->set('deleteName', $workspace->name)
        ->call('deleteWorkspace')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('workspaces', [
        'id' => $workspace->id,
    ]);
});

test('workspace deletion requires name confirmation', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $workspace])
        ->set('deleteName', 'Wrong Name')
        ->call('deleteWorkspace')
        ->assertHasErrors(['deleteName']);

    $this->assertDatabaseHas('workspaces', [
        'id' => $workspace->id,
        'deleted_at' => null,
    ]);
});

test('deleting current workspace switches to alphabetically first remaining workspace', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluWorkspace = Workspace::factory()->create(['name' => 'Zulu Workspace']);
    $zuluWorkspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $alphaWorkspace = Workspace::factory()->create(['name' => 'Alpha Workspace']);
    $alphaWorkspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $betaWorkspace = Workspace::factory()->create(['name' => 'Beta Workspace']);
    $betaWorkspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $user->update(['current_workspace_id' => $zuluWorkspace->id]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $zuluWorkspace])
        ->set('deleteName', $zuluWorkspace->name)
        ->call('deleteWorkspace')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('workspaces', [
        'id' => $zuluWorkspace->id,
    ]);

    expect($user->fresh()->current_workspace_id)->toEqual($alphaWorkspace->id);
});

test('deleting current workspace falls back to personal workspace when alphabetically first', function () {
    $user = User::factory()->create();
    $personalWorkspace = $user->personalWorkspace();
    $workspace = Workspace::factory()->create(['name' => 'Zulu Workspace']);
    $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $user->update(['current_workspace_id' => $workspace->id]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $workspace])
        ->set('deleteName', $workspace->name)
        ->call('deleteWorkspace')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('workspaces', [
        'id' => $workspace->id,
    ]);

    expect($user->fresh()->current_workspace_id)->toEqual($personalWorkspace->id);
});

test('deleting non current workspace leaves current workspace unchanged', function () {
    $user = User::factory()->create();
    $personalWorkspace = $user->personalWorkspace();
    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $user->update(['current_workspace_id' => $personalWorkspace->id]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $workspace])
        ->set('deleteName', $workspace->name)
        ->call('deleteWorkspace')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('workspaces', [
        'id' => $workspace->id,
    ]);

    expect($user->fresh()->current_workspace_id)->toEqual($personalWorkspace->id);
});

test('members can leave non personal workspaces', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($member);

    Livewire::test('pages::workspaces.index')
        ->call('leaveWorkspace', $workspace->id)
        ->assertHasNoErrors();

    expect($member->fresh()->belongsToWorkspace($workspace))->toBeFalse();
});

test('leaving current workspace switches to alphabetically first remaining workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['name' => 'Mike']);

    $zuluWorkspace = Workspace::factory()->create(['name' => 'Zulu Workspace']);
    $zuluWorkspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $zuluWorkspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $alphaWorkspace = Workspace::factory()->create(['name' => 'Alpha Workspace']);
    $alphaWorkspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $betaWorkspace = Workspace::factory()->create(['name' => 'Beta Workspace']);
    $betaWorkspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $member->update(['current_workspace_id' => $zuluWorkspace->id]);

    $this->actingAs($member);

    Livewire::test('pages::workspaces.index')
        ->call('leaveWorkspace', $zuluWorkspace->id)
        ->assertHasNoErrors();

    expect($member->fresh()->belongsToWorkspace($zuluWorkspace))->toBeFalse();
    expect($member->fresh()->current_workspace_id)->toEqual($alphaWorkspace->id);
});

test('personal workspaces cannot be left', function () {
    $user = User::factory()->create();
    $personalWorkspace = $user->personalWorkspace();

    $this->actingAs($user);

    Livewire::test('pages::workspaces.index')
        ->call('leaveWorkspace', $personalWorkspace->id)
        ->assertForbidden();

    expect($user->fresh()->belongsToWorkspace($personalWorkspace))->toBeTrue();
});

test('workspace owners cannot leave their workspace', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $this->actingAs($owner);

    Livewire::test('pages::workspaces.index')
        ->call('leaveWorkspace', $workspace->id)
        ->assertForbidden();

    expect($owner->fresh()->belongsToWorkspace($workspace))->toBeTrue();
});

test('users cannot leave workspaces they dont belong to', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::workspaces.index')
        ->call('leaveWorkspace', $workspace->id)
        ->assertForbidden();
});

test('leave control is only rendered for leaveable workspaces', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $leaveableWorkspace = Workspace::factory()->create();

    $leaveableWorkspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $leaveableWorkspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($member);

    Livewire::test('pages::workspaces.index')
        ->assertSeeHtml('data-test="workspace-leave-button"');
});

test('leave control is not rendered for personal or owned workspaces', function () {
    $user = User::factory()->create();
    $ownedWorkspace = Workspace::factory()->create();

    $ownedWorkspace->members()->attach($user, ['role' => WorkspaceRole::Owner->value]);

    $this->actingAs($user);

    Livewire::test('pages::workspaces.index')
        ->assertDontSeeHtml('data-test="workspace-leave-button"');
});

test('deleting workspace switches other affected users to their personal workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $workspace = Workspace::factory()->create();
    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $owner->update(['current_workspace_id' => $workspace->id]);
    $member->update(['current_workspace_id' => $workspace->id]);

    $this->actingAs($owner);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $workspace])
        ->set('deleteName', $workspace->name)
        ->call('deleteWorkspace')
        ->assertHasNoErrors();

    expect($member->fresh()->current_workspace_id)->toEqual($member->personalWorkspace()->id);
});

test('personal workspaces cannot be deleted', function () {
    $user = User::factory()->create();

    $personalWorkspace = $user->personalWorkspace();

    $this->actingAs($user);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $personalWorkspace])
        ->set('deleteName', $personalWorkspace->name)
        ->call('deleteWorkspace')
        ->assertForbidden();

    $this->assertDatabaseHas('workspaces', [
        'id' => $personalWorkspace->id,
        'deleted_at' => null,
    ]);
});

test('workspaces cannot be deleted by non owners', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($member);

    Livewire::test('pages::workspaces.delete-workspace-modal', ['workspace' => $workspace])
        ->set('deleteName', $workspace->name)
        ->call('deleteWorkspace')
        ->assertForbidden();
});

test('guests cannot access workspaces', function () {
    $response = $this->get(route('workspaces.index'));

    $response->assertRedirect(route('login'));
});
