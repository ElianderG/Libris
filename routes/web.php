<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::inertia('/catalogo', 'Catalog')->name('catalog');
Route::inertia('/carrinho', 'Cart')->name('cart');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/biblioteca', 'Library')->name('library');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::inertia('/admin', 'admin/Home')->name('admin.home');
});

require __DIR__.'/settings.php';
