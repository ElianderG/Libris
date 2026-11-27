<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
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

function orderReadyForRetry(string $paymentStatus, string $orderStatus = Order::STATUS_PENDING_PAYMENT): Order
{
    $user = User::factory()->create();
    app(Cart::class)->add('a-janela-do-terceiro-andar');
    $order = app(PlaceOrder::class)->place($user);
    $order->status = $orderStatus;
    $order->save();

    $payment = new Payment;
    $payment->order_id = $order->id;
    $payment->gateway_id = 'payments_antigo';
    $payment->amount_cents = $order->total_cents;
    $payment->status = $paymentStatus;
    $payment->method = Payment::METHOD_PIX;
    $payment->raw_response = [
        'error' => 'request_error',
        'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_antigo',
    ];
    $payment->save();

    return $order->fresh();
}

it('explica o pix recusado e abre outra tentativa no mesmo pedido', function () {
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_novo',
            'status' => 'pending',
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_novo',
        ], 201),
        'https://gateway.test/payments/payments_novo' => Http::response([
            'id' => 'payments_novo',
            'status' => 'pending',
        ]),
    ]);

    $order = orderReadyForRetry(Payment::STATUS_FAILED);

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertDontSee('request_error')
        ->assertInertia(fn ($page) => $page
            ->where('explanation', 'Não conseguimos concluir este Pix.')
            ->where('canRetry', true)
            ->has('attempts', 1)
            ->where('attempts.0.active', true)
            ->where('attempts.0.status', Payment::STATUS_FAILED)
            ->missing('attempts.0.gateway_id'));

    $this->actingAs($order->user)
        ->post(route('payments.store', $order))
        ->assertRedirect(route('payments.show', $order));

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canRetry', false)
            ->where('explanation', null)
            ->has('attempts', 2)
            ->where('attempts.0.active', true)
            ->where('attempts.0.status', Payment::STATUS_PENDING)
            ->where('attempts.1.active', false)
            ->where('attempts.1.status', Payment::STATUS_FAILED)
            ->missing('attempts.0.gateway_id'));

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT)
        ->and(Payment::query()->where('order_id', $order->id)->count())->toBe(2);
});

it('não oferece novo pix quando o pedido já está pago', function () {
    Http::fake();

    $order = orderReadyForRetry(Payment::STATUS_PAID, Order::STATUS_PAID);

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canRetry', false)
            ->where('explanation', null)
            ->where('attempts.0.label', 'Pago'));

    $this->actingAs($order->user)
        ->post(route('payments.store', $order))
        ->assertRedirect(route('payments.show', $order))
        ->assertSessionHas('error');

    expect(Payment::query()->where('order_id', $order->id)->count())->toBe(1)
        ->and(collect(Http::recorded())->filter(fn (array $pair): bool => str_contains($pair[0]->url(), 'gateway.test'))->count())->toBe(0);
});

it('repete a pagsmile no mesmo pedido sem apagar a tentativa anterior', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);
    Http::fake([
        'https://pagsmile.test/trade/pay' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-novo',
            'trade_status' => 'PROCESSING',
            'qr_code' => '00020126580014br.gov.bcb.pix',
        ]),
    ]);

    $order = orderReadyForRetry(Payment::STATUS_FAILED);
    $order->gateway = Order::GATEWAY_PAGSMILE;
    $order->save();
    $previous = $order->payments()->first();
    $previous->gateway = Order::GATEWAY_PAGSMILE;
    $previous->save();

    $this->actingAs($order->user)
        ->from(route('payments.show', $order))
        ->post(route('payments.store', $order), [
            'gateway' => Order::GATEWAY_JUNTOS,
            'document' => '12',
        ])
        ->assertRedirect(route('payments.show', $order))
        ->assertSessionHasErrors('document');

    expect(Payment::query()->where('order_id', $order->id)->count())->toBe(1);
    Http::assertNothingSent();

    $this->actingAs($order->user)
        ->post(route('payments.store', $order), [
            'gateway' => Order::GATEWAY_JUNTOS,
            'document' => '12345678901',
        ])
        ->assertRedirect(route('payments.show', $order));

    $fresh = $order->fresh();
    $latest = $fresh->payments()->latest('id')->first();

    expect($fresh->gateway)->toBe(Order::GATEWAY_PAGSMILE)
        ->and(Payment::query()->where('order_id', $order->id)->count())->toBe(2)
        ->and($previous->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($latest->gateway)->toBe(Order::GATEWAY_PAGSMILE)
        ->and($latest->gateway_id)->toBe('trade-novo')
        ->and(json_encode($latest->raw_request))->not->toContain('12345678901');
});

it('explica cobrança encerrada ou expirada', function (string $status, string $phrase) {
    Http::fake();

    $order = orderReadyForRetry($status);

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertDontSee('request_error')
        ->assertInertia(fn ($page) => $page
            ->where('explanation', $phrase)
            ->where('canRetry', true)
            ->where('attempts.0.active', true)
            ->where('attempts.0.status', $status));

    expect(collect(Http::recorded())->filter(fn (array $pair): bool => str_contains($pair[0]->url(), 'gateway.test'))->count())->toBe(0);
})->with([
    [Payment::STATUS_CANCELED, 'Esta tentativa foi encerrada.'],
    [Payment::STATUS_EXPIRED, 'Este Pix expirou.'],
]);
