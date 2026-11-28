<?php

use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\Settings\ProfileAvatarController;
use Illuminate\Support\Facades\Route;

Route::get('/avatars/{avatar}', [ProfileAvatarController::class, 'show'])
    ->whereNumber('avatar')
    ->name('profile.avatars.show');
Route::get('/', HomeController::class)->name('home');
Route::get('/catalogo', CatalogController::class)->name('catalog');
Route::get('/livros/{slug}', BookController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('books.show');
Route::get('/carrinho', [CartController::class, 'show'])->name('cart');
Route::post('/carrinho', [CartController::class, 'store'])->name('cart.store');
Route::delete('/carrinho', [CartController::class, 'clear'])->name('cart.clear');
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
    Route::get('/minha-conta/configuracoes', [ApiKeyController::class, 'index'])->name('account.settings');
    Route::post('/minha-conta/configuracoes', [ApiKeyController::class, 'store'])->name('account.settings.store');
    Route::delete('/minha-conta/configuracoes', [ApiKeyController::class, 'destroy'])->name('account.settings.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/biblioteca', [LibraryController::class, 'index'])->name('library');
    Route::get('/biblioteca/{slug}', [LibraryController::class, 'show'])
        ->where('slug', '[a-z0-9\-]+')
        ->name('library.show');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', AdminHomeController::class)->name('admin.home');
    Route::get('/admin/livros', [AdminBookController::class, 'index'])->name('admin.books');
    Route::get('/admin/livros/novo', [AdminBookController::class, 'create'])->name('admin.books.create');
    Route::post('/admin/livros', [AdminBookController::class, 'store'])->name('admin.books.store');
    Route::get('/admin/livros/{product}/editar', [AdminBookController::class, 'edit'])->whereNumber('product')->name('admin.books.edit');
    Route::put('/admin/livros/{product}', [AdminBookController::class, 'update'])->whereNumber('product')->name('admin.books.update');
    Route::delete('/admin/livros/{product}', [AdminBookController::class, 'destroy'])->whereNumber('product')->name('admin.books.destroy');
    Route::post('/admin/livros/{product}/despublicar', [AdminBookController::class, 'unpublish'])->whereNumber('product')->name('admin.books.unpublish');
    Route::get('/admin/categorias', [AdminCategoryController::class, 'index'])->name('admin.categories');
    Route::get('/admin/categorias/nova', [AdminCategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/admin/categorias', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/admin/categorias/{category}/editar', [AdminCategoryController::class, 'edit'])->whereNumber('category')->name('admin.categories.edit');
    Route::put('/admin/categorias/{category}', [AdminCategoryController::class, 'update'])->whereNumber('category')->name('admin.categories.update');
    Route::delete('/admin/categorias/{category}', [AdminCategoryController::class, 'destroy'])->whereNumber('category')->name('admin.categories.destroy');
    Route::get('/admin/pedidos', [AdminOrderController::class, 'index'])->name('admin.orders');
    Route::get('/admin/pedidos/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('admin.orders.show');
    Route::post('/admin/pedidos/{order}/pagamentos/{payment}', [AdminOrderController::class, 'settle'])
        ->whereNumber(['order', 'payment'])
        ->name('admin.orders.settle');
    Route::get('/admin/usuarios', [AdminUserController::class, 'index'])->name('admin.users');
    Route::post('/admin/usuarios/{user}', [AdminUserController::class, 'update'])->whereNumber('user')->name('admin.users.update');
    Route::delete('/admin/usuarios/{user}', [AdminUserController::class, 'destroy'])->whereNumber('user')->name('admin.users.destroy');
    Route::get('/admin/afiliados', [AdminAffiliateController::class, 'index'])->name('admin.affiliates');
});

require __DIR__.'/settings.php';
