<?php

use Modules\Workspace\Middleware\EnsureWorkspaceMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::livewire('invitations/{invitation}/accept', 'workspace::pages.accept-invitation')
        ->name('invitations.accept');
});

Route::middleware(['web', 'auth', 'verified'])->group(function (): void {
    Route::livewire('settings/workspaces', 'workspace::pages.index')->name('workspaces.index');

    Route::middleware(EnsureWorkspaceMembership::class)->group(function (): void {
        Route::livewire('settings/workspaces/{workspace}', 'workspace::pages.edit')->name('workspaces.edit');
    });

    Route::prefix('{current_workspace}')
        ->middleware(EnsureWorkspaceMembership::class)
        ->group(function (): void {
            Route::view('dashboard', 'dashboard')->name('workspace.dashboard');
        });
});
