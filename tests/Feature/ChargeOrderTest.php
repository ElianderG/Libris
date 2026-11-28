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
use Illuminate\Support\Facades\Log;
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

    Http::assertSent(function ($request) use ($order): bool {
        return $request->url() === 'https://gateway.test/payments'
            && $request['amount'] === $order->total_cents / 100;
    });

    expect($payment->gateway_id)->toBe('payments_abc')
        ->and($payment->amount_cents)->toBe($order->total_cents)
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

it('cobra na pagsmile e não grava o cpf', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);

    Http::fake([
        'https://pagsmile.test/trade/pay' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-38',
            'trade_status' => 'PROCESSING',
            'qr_code' => '00020126580014br.gov.bcb.pix',
            'qr_code_url' => 'https://pagsmile.test/qr/38',
            'qr_code_img' => 'data:image/png;base64,abc',
        ]),
    ]);

    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    $order = pendingOrder();
    $order->gateway = Order::GATEWAY_PAGSMILE;
    $order->save();

    $payment = app(ChargeOrder::class)->charge($order, $order->user, '123.456.789-01');
    $stored = json_encode([$payment->raw_request, $payment->raw_response, $payment->getAttributes()]);

    expect($order->fresh()->gateway)->toBe(Order::GATEWAY_PAGSMILE)
        ->and($payment->gateway)->toBe(Order::GATEWAY_PAGSMILE)
        ->and($payment->gateway_id)->toBe('trade-38')
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->raw_response['qr_code'] ?? null)->toBe('00020126580014br.gov.bcb.pix')
        ->and($payment->raw_response['qr_code_url'] ?? null)->toBe('https://pagsmile.test/qr/38')
        ->and($stored)->not->toContain('12345678901')
        ->and($stored)->not->toContain('chave-secreta-teste')
        ->and(implode("\n", $logged))->not->toContain('12345678901')
        ->and(implode("\n", $logged))->not->toContain('chave-secreta-teste');

    Http::assertSent(function ($request) use ($order, $payment): bool {
        return $request->url() === 'https://pagsmile.test/trade/pay'
            && $request['out_trade_no'] === (string) $payment->id
            && $request['buyer_id'] === (string) $order->user_id
            && $request['subject'] === 'A janela do terceiro andar'
            && $request['order_amount'] === '37.90'
            && $request['order_currency'] === 'BRL'
            && $request['notify_url'] === url('/webhooks/pagsmile')
            && $request['customer']['identify']['number'] === '12345678901';
    });
});

it('não chama a pagsmile com cpf curto nem abaixo do mínimo', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);
    Http::fake();

    $order = pendingOrder();
    $order->gateway = Order::GATEWAY_PAGSMILE;
    $order->save();

    $short = app(ChargeOrder::class)->charge($order, $order->user, '123');

    expect($short->status)->toBe(Payment::STATUS_FAILED)
        ->and($short->gateway_id)->toBeNull()
        ->and(json_encode($short->raw_request))->not->toContain('123')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    $order->total_cents = 5;
    $order->save();

    $low = app(ChargeOrder::class)->charge($order->fresh(), $order->user, '12345678901');

    expect($low->status)->toBe(Payment::STATUS_FAILED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Http::assertNothingSent();
});

it('ignora o cpf quando a escolha é o juntos se faz', function () {
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_juntos',
            'status' => 'pending',
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_juntos',
        ], 201),
    ]);

    $order = pendingOrder();
    $payment = app(ChargeOrder::class)->charge($order, $order->user, '12345678901');

    expect($payment->gateway)->toBe(Order::GATEWAY_JUNTOS)
        ->and($payment->gateway_id)->toBe('payments_juntos')
        ->and(json_encode($payment->raw_request))->not->toContain('12345678901');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://gateway.test/payments'
        && ! str_contains($request->body(), '12345678901'));
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
