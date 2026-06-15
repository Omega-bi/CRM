<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('workspace switcher prompts creation when user has no workspace', function () {
    $user = User::factory()->create();

    $user->workspaces()->detach();
    $user->forceFill(['current_workspace_id' => null])->save();

    $response = $this
        ->actingAs($user->fresh())
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSeeHtml('data-test="workspace-switcher-create-button"')
        ->assertSeeInOrder([
            'data-test="workspace-switcher-create-button"',
            __('Create workspace'),
        ], false)
        ->assertDontSeeHtml('data-test="workspace-switcher-trigger"')
        ->assertDontSee(__('Choose workspace'));
});

test('workspace switcher prompts selection when user has a workspace', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSeeHtml('data-test="workspace-switcher-trigger"')
        ->assertSeeInOrder([
            'data-test="workspace-switcher-trigger"',
            __('Choose workspace'),
        ], false)
        ->assertSee(__('Create workspace'));
});
