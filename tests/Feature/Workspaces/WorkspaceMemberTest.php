<?php

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\User;
use Livewire\Livewire;

test('workspace member role can be updated by owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($owner);

    Livewire::test('pages::workspaces.edit', ['workspace' => $workspace])
        ->call('updateMember', $member->id, WorkspaceRole::Admin->value)
        ->assertHasNoErrors();

    expect($workspace->members()->where('user_id', $member->id)->first()->pivot->role->value)->toEqual(WorkspaceRole::Admin->value);
});

test('workspace member role cannot be updated by non owner', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($admin, ['role' => WorkspaceRole::Admin->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($admin);

    Livewire::test('pages::workspaces.edit', ['workspace' => $workspace])
        ->call('updateMember', $member->id, WorkspaceRole::Admin->value)
        ->assertForbidden();
});

test('workspace member can be removed by owner', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($owner);

    Livewire::test('pages::workspaces.remove-member-modal', ['workspace' => $workspace])
        ->set('memberId', $member->id)
        ->call('removeMember')
        ->assertHasNoErrors();

    expect($member->fresh()->belongsToWorkspace($workspace))->toBeFalse();
});

test('workspace member cannot be removed by non owners', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($admin, ['role' => WorkspaceRole::Admin->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($admin);

    Livewire::test('pages::workspaces.remove-member-modal', ['workspace' => $workspace])
        ->set('memberId', $member->id)
        ->call('removeMember')
        ->assertForbidden();
});

test('removed members current workspace is set to personal workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $personalWorkspace = $member->personalWorkspace();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $member->update(['current_workspace_id' => $workspace->id]);

    $this->actingAs($owner);

    Livewire::test('pages::workspaces.remove-member-modal', ['workspace' => $workspace])
        ->set('memberId', $member->id)
        ->call('removeMember')
        ->assertHasNoErrors();

    expect($member->fresh()->current_workspace_id)->toEqual($personalWorkspace->id);
});