<?php

use App\Http\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/me', [AccountController::class, 'me'])->name('api.me');
    Route::get('/me/compras', [AccountController::class, 'purchases'])->name('api.purchases');
    Route::get('/me/compras/{order}', [AccountController::class, 'purchase'])
        ->whereNumber('order')
        ->name('api.purchases.show');
    Route::get('/me/vendas', [AccountController::class, 'sales'])->name('api.sales');
});
