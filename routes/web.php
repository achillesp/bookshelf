<?php

use App\Http\Controllers\LibraryEntryController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('search', SearchController::class)->name('search');
    Route::get('library', [LibraryEntryController::class, 'index'])->name('library.index');
    Route::post('library', [LibraryEntryController::class, 'store'])->name('library.store');
    Route::get('library/{entry}', [LibraryEntryController::class, 'show'])->whereNumber('entry')->name('library.show');
    Route::patch('library/{entry}', [LibraryEntryController::class, 'update'])->whereNumber('entry')->name('library.update');
    Route::delete('library/{entry}', [LibraryEntryController::class, 'destroy'])->whereNumber('entry')->name('library.destroy');
});

require __DIR__.'/settings.php';
