<?php


use App\Http\Controllers\ExportItemsController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\SwitchWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/invitations/accept/{token}', InvitationAcceptController::class)
    ->middleware(['auth', 'signed'])
    ->name('invitations.accept');

Route::post('/workspaces/switch', SwitchWorkspaceController::class)
    ->middleware('auth')
    ->name('workspaces.switch');

Route::get('/exports/items', ExportItemsController::class)
    ->middleware('auth')
    ->name('exports.items');

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
