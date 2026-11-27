<?php

use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalogo', CatalogController::class)->name('catalog');
Route::get('/livros/{slug}', BookController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('books.show');
Route::get('/carrinho', [CartController::class, 'show'])->name('cart');
Route::post('/carrinho', [CartController::class, 'store'])->name('cart.store');
Route::delete('/carrinho/{slug}', [CartController::class, 'destroy'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('cart.destroy');

Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/pedidos/{order}/pagamento', [CheckoutController::class, 'payment'])->name('payments.show');
    Route::post('/pedidos/{order}/pagamento', [CheckoutController::class, 'refresh'])->name('payments.refresh');
    Route::post('/checkout/pedidos/{order}/pagamento', [CheckoutController::class, 'retry'])->name('payments.store');
    Route::get('/minha-conta/compras', [PurchaseController::class, 'index'])->name('purchases');
    Route::get('/minha-conta/compras/{order}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/minha-conta/afiliado', [AffiliateController::class, 'index'])->name('affiliate');
});

Route::get('/biblioteca/{slug}', [LibraryController::class, 'show'])
    ->where('slug', '[a-z0-9\-]+')
    ->name('library.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/biblioteca', [LibraryController::class, 'index'])->name('library');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::inertia('/admin', 'admin/Home')->name('admin.home');
});

require __DIR__.'/settings.php';
