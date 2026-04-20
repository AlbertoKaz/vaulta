<?php



use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'workspace'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/collections', 'collections.index')
        ->name('collections.index');

    Route::livewire('/collections/{collection}/items', 'items.index')
        ->name('items.index');

});

require __DIR__.'/settings.php';
