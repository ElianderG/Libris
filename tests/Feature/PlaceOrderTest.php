<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

it('grava o pedido com preço congelado e esvazia a sacola', function () {
    $user = User::factory()->create();
    $cart = app(Cart::class);
    $cart->add('a-janela-do-terceiro-andar');
    $cart->add('o-mapa-que-cabia-no-bolso');

    $order = app(PlaceOrder::class)->place($user);

    expect($order->status)->toBe(Order::STATUS_PENDING_PAYMENT)
        ->and($order->user_id)->toBe($user->id)
        ->and($order->total_cents)->toBe(6290)
        ->and($order->affiliate_id)->toBeNull()
        ->and($order->affiliate_code)->toBeNull()
        ->and($order->items)->toHaveCount(2)
        ->and($cart->items())->toHaveCount(0);

    $snapshot = $order->items()->where('title', 'A janela do terceiro andar')->firstOrFail();
    expect($snapshot->unit_price_cents)->toBe(3790)
        ->and($snapshot->quantity)->toBe(1);

    $book = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();
    $book->price_cents = 1000;
    $book->save();

    expect($order->fresh()->total_cents)->toBe(6290)
        ->and($snapshot->fresh()->unit_price_cents)->toBe(3790);
});

it('não cria pedido quando a sacola está vazia', function () {
    $user = User::factory()->create();

    expect(fn () => app(PlaceOrder::class)->place($user))->toThrow(RuntimeException::class);
    expect(Order::query()->count())->toBe(0);
});

it('desfaz o pedido quando a transação falha no meio', function () {
    $user = User::factory()->create();
    $cart = app(Cart::class);
    $cart->add('a-janela-do-terceiro-andar');

    OrderItem::creating(function (): void {
        throw new RuntimeException('falha');
    });

    try {
        expect(fn () => app(PlaceOrder::class)->place($user))->toThrow(RuntimeException::class);
    } finally {
        OrderItem::flushEventListeners();
    }

    expect(Order::query()->count())->toBe(0)
        ->and(OrderItem::query()->count())->toBe(0)
        ->and($cart->items())->toHaveCount(1);
});
