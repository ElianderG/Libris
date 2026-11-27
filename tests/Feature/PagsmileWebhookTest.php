<?php

use App\Events\PaymentConfirmed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);
});

it('recusa assinatura errada sem alterar o pagamento', function () {
    Http::fake();

    $order = pagsmilePayment();
    $payment = $order->payments()->first();
    $updatedAt = $payment->updated_at;
    $raw = noticeBody($payment, '37.90', 'SUCCESS');

    postNotice($raw, 't='.time().',v2=assinatura-errada')
        ->assertUnauthorized();

    postNotice($raw, signature($raw, time() - 3600))
        ->assertUnauthorized();

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($payment->fresh()->updated_at?->equalTo($updatedAt))->toBeTrue()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Http::assertNothingSent();
});

it('confirma o aviso pago uma vez e ignora a repetição', function () {
    Event::fake([PaymentConfirmed::class]);

    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake([
        'https://pagsmile.test/trade/query' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-39',
            'trade_status' => 'SUCCESS',
            'order_amount' => '37.90',
            'qr_code' => '00020126580014br.gov.bcb.pix',
            'customer' => [
                'identify' => [
                    'number' => '12345678901',
                    'type' => 'CPF',
                ],
            ],
        ]),
    ]);

    $order = pagsmilePayment();
    $payment = $order->payments()->first();
    $raw = noticeBody($payment, '37.90', 'SUCCESS');

    postNotice($raw, signature($raw))
        ->assertOk()
        ->assertSee('success');

    $paidAt = $payment->fresh()->paid_at;

    postNotice($raw, signature($raw))
        ->assertOk()
        ->assertSee('success');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PAID)
        ->and($payment->fresh()->paid_at?->equalTo($paidAt))->toBeTrue()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID)
        ->and(json_encode($payment->fresh()->raw_response))->not->toContain('12345678901')
        ->and(implode("\n", $logged))->not->toContain('12345678901')
        ->and(implode("\n", $logged))->not->toContain('chave-secreta-teste');

    Event::assertDispatchedTimes(PaymentConfirmed::class, 1);

    $consultas = collect(Http::recorded())
        ->filter(fn (array $pair): bool => $pair[0]->url() === 'https://pagsmile.test/trade/query')
        ->count();

    expect($consultas)->toBe(1);
});

it('não marca pago quando o valor do aviso diverge', function () {
    Http::fake([
        'https://pagsmile.test/trade/query' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-39',
            'trade_status' => 'SUCCESS',
            'order_amount' => '37.90',
        ]),
    ]);

    $order = pagsmilePayment();
    $payment = $order->payments()->first();
    $raw = noticeBody($payment, '1.00', 'SUCCESS');

    postNotice($raw, signature($raw))
        ->assertOk();

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Http::assertNothingSent();
});

it('não marca pago quando a consulta diverge do pedido', function () {
    Http::fake([
        'https://pagsmile.test/trade/query' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-39',
            'trade_status' => 'SUCCESS',
            'order_amount' => '10.00',
        ]),
    ]);

    $order = pagsmilePayment();
    $payment = $order->payments()->first();
    $raw = noticeBody($payment, '37.90', 'SUCCESS');

    postNotice($raw, signature($raw))
        ->assertOk();

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

it('não atualiza uma tentativa do juntos se faz', function () {
    Http::fake();

    $order = pagsmilePayment();
    $payment = $order->payments()->first();
    $payment->gateway = Order::GATEWAY_JUNTOS;
    $payment->save();

    $raw = noticeBody($payment, '37.90', 'SUCCESS');

    postNotice($raw, signature($raw))
        ->assertOk()
        ->assertSee('success');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->fresh()->gateway)->toBe(Order::GATEWAY_JUNTOS)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Http::assertNothingSent();
});

function pagsmilePayment(): Order
{
    $user = User::factory()->create();
    app(Cart::class)->add('a-janela-do-terceiro-andar');
    $order = app(PlaceOrder::class)->place($user, Order::GATEWAY_PAGSMILE);

    $payment = new Payment;
    $payment->order_id = $order->id;
    $payment->gateway = Order::GATEWAY_PAGSMILE;
    $payment->gateway_id = 'trade-39';
    $payment->amount_cents = $order->total_cents;
    $payment->status = Payment::STATUS_PENDING;
    $payment->method = Payment::METHOD_PIX;
    $payment->save();

    return $order->fresh();
}

function noticeBody(Payment $payment, string $amount, string $status): string
{
    return json_encode([
        'amount' => $amount,
        'out_trade_no' => (string) $payment->id,
        'method' => 'PIX',
        'trade_status' => $status,
        'trade_no' => 'trade-39',
        'currency' => 'BRL',
        'app_id' => 'app-id-teste',
        'user' => [
            'identify' => [
                'number' => '12345678901',
                'type' => 'CPF',
            ],
        ],
        'timestamp' => (string) time(),
    ], JSON_THROW_ON_ERROR);
}

function signature(string $raw, ?int $timestamp = null): string
{
    $timestamp ??= time();
    $sign = hash_hmac('sha256', $raw, 'chave-secreta-teste');

    return "t={$timestamp}, v2={$sign}";
}

function postNotice(string $raw, string $signature): TestResponse
{
    return test()->call('POST', '/webhooks/pagsmile', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'text/plain',
        'HTTP_PAGSMILE_SIGNATURE' => $signature,
    ], $raw);
}
