<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalogo', CatalogController::class)->name('catalog');
Route::get('/livros/{slug}', BookController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('books.show');
Route::inertia('/carrinho', 'Cart')->name('cart');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/biblioteca', 'Library')->name('library');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::inertia('/admin', 'admin/Home')->name('admin.home');
});

require __DIR__.'/settings.php';
