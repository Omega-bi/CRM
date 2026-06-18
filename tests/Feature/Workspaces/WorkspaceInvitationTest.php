<?php

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Models\WorkspaceInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('workspace invitations can be created', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $this->actingAs($owner);

    Livewire::test('workspace::pages.invite-member-modal', ['workspace' => $workspace])
        ->set('inviteEmail', 'invited@example.com')
        ->set('inviteRole', WorkspaceRole::Member->value)
        ->call('createInvitation')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('workspace_invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'role' => WorkspaceRole::Member->value,
    ]);
});

test('workspace invitations cannot be created by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);
    $workspace->members()->attach($member, ['role' => WorkspaceRole::Member->value]);

    $this->actingAs($member);

    Livewire::test('workspace::pages.invite-member-modal', ['workspace' => $workspace])
        ->set('inviteEmail', 'invited@example.com')
        ->set('inviteRole', WorkspaceRole::Member->value)
        ->call('createInvitation')
        ->assertForbidden();
});

test('workspace invitations can be cancelled by owner', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner);

    Livewire::test('workspace::pages.cancel-invitation-modal', ['workspace' => $workspace])
        ->set('invitationCode', $invitation->code)
        ->call('cancelInvitation')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('workspace_invitations', [
        'id' => $invitation->id,
    ]);
});

test('workspace invitations can be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'role' => WorkspaceRole::Member,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser);

    $response = Livewire::test('workspace::pages.accept-invitation', [
        'invitation' => $invitation,
    ]);

    $response->assertRedirect(route('dashboard'));

    expect(session('workspace-invitation-accepted'))->toBeTrue();

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect($invitedUser->fresh()->belongsToWorkspace($workspace))->toBeTrue();
});

test('accepted invitation toast is shown on the dashboard', function () {
    $user = User::factory()->create();

    session()->flash('workspace-invitation-accepted', true);

    $this->actingAs($user);

    Livewire::test('workspace::pages.pending-invitations-modal')
        ->assertDispatched('toast-show');
});

test('pending invitations excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $workspace = Workspace::factory()->create(['name' => 'Expired Workspace']);

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $invitation = WorkspaceInvitation::factory()->expired()->create([
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser);

    Livewire::test('workspace::pages.pending-invitations-modal')
        ->assertDontSee('Expired Workspace');

    $this->assertDatabaseHas('workspace_invitations', [
        'id' => $invitation->id,
    ]);
});

test('workspace invitations cannot be accepted by user that wasnt invited', function () {
    $owner = User::factory()->create();
    $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $invitation = WorkspaceInvitation::factory()->create([
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($uninvitedUser);

    $response = Livewire::test('workspace::pages.accept-invitation', [
        'invitation' => $invitation,
    ]);

    $response->assertHasErrors(['invitation']);

    expect($uninvitedUser->fresh()->belongsToWorkspace($workspace))->toBeFalse();
});

test('expired invitations cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $workspace = Workspace::factory()->create();

    $workspace->members()->attach($owner, ['role' => WorkspaceRole::Owner->value]);

    $invitation = WorkspaceInvitation::factory()->expired()->create([
        'workspace_id' => $workspace->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser);

    $response = Livewire::test('workspace::pages.accept-invitation', [
        'invitation' => $invitation,
    ]);

    $response->assertHasErrors(['invitation']);

    expect($invitedUser->fresh()->belongsToWorkspace($workspace))->toBeFalse();
});