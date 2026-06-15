<?php

use App\Http\Middleware\EnsureWorkspaceMembership;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::prefix('{current_workspace}')
    ->middleware(['auth', 'verified', EnsureWorkspaceMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('workspace.dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::workspaces.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
