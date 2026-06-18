<?php

use App\Enums\ProjectRole;
use App\Enums\ProjectStatus;
use Modules\Workspace\Enums\WorkspaceRole;
use App\Models\Project;
use Modules\Workspace\Models\Workspace;
use App\Models\User;

test('workspace owns a portfolio of construction projects', function () {
    $workspace = Workspace::factory()->create(['name' => 'Ostara Projects']);

    $school = Project::factory()->for($workspace)->create([
        'name' => 'School Construction',
        'status' => ProjectStatus::Active,
    ]);
    $dam = Project::factory()->for($workspace)->create([
        'name' => 'Dam Construction',
    ]);

    expect($workspace->projects)
        ->toHaveCount(2)
        ->pluck('id')
        ->toContain($school->id, $dam->id);
});

test('project memberships keep project roles separate from workspace permissions', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();

    $workspace->members()->attach($user, [
        'role' => WorkspaceRole::Member->value,
        'position' => 'technical_supervisor',
    ]);

    $project->members()->attach($user, [
        'workspace_id' => $workspace->id,
        'role' => ProjectRole::TechnicalSupervisor->value,
    ]);

    $workspaceMembership = $user->workspaceMemberships()
        ->whereBelongsTo($workspace)
        ->first();

    expect($workspaceMembership?->role)->toBe(WorkspaceRole::Member);
    expect($workspaceMembership?->position)->toBe('technical_supervisor');
    expect($project->members()->first()?->pivot->role)->toBe(ProjectRole::TechnicalSupervisor);
});

test('projects are scoped to their workspace', function () {
    $ostara = Workspace::factory()->create(['name' => 'Ostara Projects']);
    $anotherWorkspace = Workspace::factory()->create(['name' => 'Another Workspace']);

    Project::factory()->for($ostara)->create(['name' => 'Residential Building Construction']);
    Project::factory()->for($anotherWorkspace)->create(['name' => 'Warehouse Construction']);

    expect($ostara->projects()->pluck('name')->all())
        ->toBe(['Residential Building Construction']);
});
