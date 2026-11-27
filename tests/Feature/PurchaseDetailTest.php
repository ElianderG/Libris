<?php

use App\Models\Order;
use App\Models\Payment;
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

it('mostra o preço da compra e as tentativas, sem o json do gateway', function () {
    $owner = User::factory()->create();
    app(Cart::class)->add('a-janela-do-terceiro-andar');
    $order = app(PlaceOrder::class)->place($owner);

    foreach (['payments_antiga', 'payments_nova'] as $gatewayId) {
        $payment = new Payment;
        $payment->order_id = $order->id;
        $payment->gateway_id = $gatewayId;
        $payment->amount_cents = $order->total_cents;
        $payment->status = Payment::STATUS_PENDING;
        $payment->method = Payment::METHOD_PIX;
        $payment->raw_response = ['segredo' => 'NAO_MOSTRAR_JSON_DO_GATEWAY'];
        $payment->save();
    }

    $product = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();
    $product->price_cents = 1000;
    $product->save();

    $this->actingAs($owner)
        ->get(route('purchases.show', $order))
        ->assertOk()
        ->assertDontSee('NAO_MOSTRAR_JSON_DO_GATEWAY')
        ->assertInertia(fn ($page) => $page
            ->component('Purchase')
            ->where('order.items.0.title', 'A janela do terceiro andar')
            ->where('order.items.0.price', 'R$ 37,90')
            ->where('order.items.0.read_url', null)
            ->where('order.total', 'R$ 37,90')
            ->has('order.payments', 2)
            ->where('order.payments.0.label', 'Pendente')
            ->missing('order.payments.0.gateway_id')
            ->missing('order.payments.0.raw_response')
            ->missing('order.payments.0.raw_request'));
});

it('mostra o caminho de leitura mesmo com o livro fora da vitrine', function () {
    $owner = User::factory()->create();
    app(Cart::class)->add('o-mapa-que-cabia-no-bolso');
    $order = app(PlaceOrder::class)->place($owner);
    $order->status = Order::STATUS_PAID;
    $order->save();

    $product = Product::query()->where('slug', 'o-mapa-que-cabia-no-bolso')->firstOrFail();
    $product->is_published = false;
    $product->save();

    $this->actingAs($owner)
        ->get(route('purchases.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('order.items.0.title', 'O mapa que cabia no bolso')
            ->where('order.items.0.read_url', '/biblioteca/o-mapa-que-cabia-no-bolso'));
});

it('responde 404 para outra conta', function () {
    $owner = User::factory()->create();
    app(Cart::class)->add('a-janela-do-terceiro-andar');
    $order = app(PlaceOrder::class)->place($owner);

    $other = User::factory()->create();
    $other->forceFill(['is_admin' => true])->save();

    $this->actingAs($other)
        ->get(route('purchases.show', $order))
        ->assertNotFound();
});
