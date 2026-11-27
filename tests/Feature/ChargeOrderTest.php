<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Services\Payments\ChargeOrder;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
    config([
        'services.juntos_se_faz.base_url' => 'https://gateway.test',
        'services.juntos_se_faz.token' => 'token-secreto-teste',
    ]);
});

function pendingOrder(): Order
{
    $user = User::factory()->create();
    $cart = app(Cart::class);
    $cart->add('a-janela-do-terceiro-andar');

    return app(PlaceOrder::class)->place($user);
}

it('preenche o gateway_id e não grava o token', function () {
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_abc',
            'status' => 'pending',
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_abc',
        ], 201),
    ]);

    $order = pendingOrder();
    $payment = app(ChargeOrder::class)->charge($order, $order->user);

    expect($payment->gateway_id)->toBe('payments_abc')
        ->and($payment->amount_cents)->toBe(3790)
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->raw_request['reference'] ?? null)->toBe((string) $payment->id)
        ->and(json_encode($payment->raw_request))->not->toContain('token-secreto-teste')
        ->and($payment->raw_response['pix_code'] ?? null)->toBe('SIMULATED_PIX_NOT_PAYABLE_payments_abc')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

it('marca o pagamento como failed e mantém o pedido pendente', function () {
    Http::fake([
        'https://gateway.test/*' => Http::response(['error' => 'request_error'], 500),
    ]);

    $order = pendingOrder();
    $payment = app(ChargeOrder::class)->charge($order, $order->user);

    expect($payment->status)->toBe(Payment::STATUS_FAILED)
        ->and($payment->gateway_id)->toBeNull()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

it('cancela a pendência anterior e abre outra cobrança', function () {
    Http::fake([
        'https://gateway.test/payments' => Http::sequence()
            ->push(['id' => 'payments_um', 'status' => 'pending'], 201)
            ->push(['id' => 'payments_dois', 'status' => 'pending'], 201),
    ]);

    $order = pendingOrder();
    $service = app(ChargeOrder::class);
    $first = $service->charge($order, $order->user);
    $second = $service->charge($order, $order->user);

    expect($first->fresh()->status)->toBe(Payment::STATUS_CANCELED)
        ->and($second->gateway_id)->toBe('payments_dois')
        ->and($second->status)->toBe(Payment::STATUS_PENDING)
        ->and(Payment::query()->where('order_id', $order->id)->count())->toBe(2);
});

it('recusa pedido de outra pessoa e pedido já pago', function () {
    Http::fake();

    $order = pendingOrder();
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    expect(fn () => app(ChargeOrder::class)->charge($order, $admin))->toThrow(RuntimeException::class);
    expect(Payment::query()->count())->toBe(0);

    $order->status = Order::STATUS_PAID;
    $order->save();

    expect(fn () => app(ChargeOrder::class)->charge($order, $order->user))->toThrow(RuntimeException::class);
    expect(Payment::query()->count())->toBe(0);
    Http::assertNothingSent();
});
