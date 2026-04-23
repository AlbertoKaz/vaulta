<?php


use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\SwitchWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/invitations/accept/{token}', InvitationAcceptController::class)
    ->middleware('auth')
    ->name('invitations.accept');

Route::post('/workspaces/switch', SwitchWorkspaceController::class)
    ->middleware('auth')
    ->name('workspaces.switch');

Route::middleware(['auth', 'workspace'])->group(function () {
    //Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/dashboard', 'dashboard')
        ->name('dashboard');

    Route::livewire('/collections', 'collections.index')
        ->name('collections.index');

    Route::livewire('/collections/{collection}/items', 'items.index')
        ->name('items.index');

});

Route::view('/members', 'members.index')
    ->middleware(['auth'])
    ->name('members.index');


require __DIR__.'/settings.php';
