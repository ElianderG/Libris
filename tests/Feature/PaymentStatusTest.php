<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
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

function orderWithPendingPayment(): Order
{
    $user = User::factory()->create();
    $cart = app(Cart::class);
    $cart->add('a-janela-do-terceiro-andar');
    $order = app(PlaceOrder::class)->place($user);

    $payment = new Payment;
    $payment->order_id = $order->id;
    $payment->gateway_id = 'payments_abc';
    $payment->amount_cents = $order->total_cents;
    $payment->status = Payment::STATUS_PENDING;
    $payment->method = Payment::METHOD_PIX;
    $payment->raw_response = [
        'id' => 'payments_abc',
        'status' => 'pending',
        'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_abc',
    ];
    $payment->save();

    return $order->fresh();
}

it('consulta ao abrir e mostra o status pago sem fechar o pedido', function () {
    Http::fake([
        'https://gateway.test/payments/payments_abc' => Http::response([
            'id' => 'payments_abc',
            'status' => 'approved',
            'pix_code' => '00020126580014br.gov.bcb.pix',
        ]),
    ]);

    $order = orderWithPendingPayment();

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('CheckoutPayment')
            ->where('payment.status', Payment::STATUS_PAID)
            ->where('payment.label', 'Pago')
            ->where('payment.gateway_id', 'payments_abc')
            ->where('payment.pix_code', '00020126580014br.gov.bcb.pix')
            ->where('payment.simulated', false)
            ->where('total', 'R$ 37,90')
            ->where('payment.created_at', $order->payments()->first()->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i')));

    $payment = $order->payments()->first();

    expect($payment->status)->toBe(Payment::STATUS_PAID)
        ->and($payment->paid_at)->toBeNull()
        ->and($payment->raw_response['pix_code'] ?? null)->toBe('00020126580014br.gov.bcb.pix')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'https://gateway.test/payments/payments_abc');
});

it('atualiza de novo pelo botão e não consulta no redirect', function () {
    Http::fake([
        'https://gateway.test/payments/payments_abc' => Http::sequence()
            ->push(['id' => 'payments_abc', 'status' => 'pending'])
            ->push(['id' => 'payments_abc', 'status' => 'approved']),
    ]);

    $order = orderWithPendingPayment();

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('payment.label', 'Pendente')
            ->where('payment.simulated', true)
            ->where('payment.pix_code', 'SIMULATED_PIX_NOT_PAYABLE_payments_abc'));

    $this->actingAs($order->user)
        ->post(route('payments.refresh', $order))
        ->assertRedirect(route('payments.show', $order));

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('payment.label', 'Pago'));

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PAID)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    $consultas = collect(Http::recorded())
        ->filter(fn (array $pair): bool => $pair[0]->url() === 'https://gateway.test/payments/payments_abc')
        ->count();

    expect($consultas)->toBe(2);
});

it('esconde o pedido de outra conta', function () {
    Http::fake();

    $order = orderWithPendingPayment();
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)
        ->get(route('payments.show', $order))
        ->assertNotFound();

    $this->actingAs($admin)
        ->post(route('payments.refresh', $order))
        ->assertNotFound();

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PENDING);
    Http::assertNothingSent();
});

it('mantém o último status quando a consulta falha', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28');
    });

    $order = orderWithPendingPayment();

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertSessionHas('error', 'Não foi possível falar com o serviço de pagamento.')
        ->assertInertia(fn ($page) => $page
            ->where('payment.status', Payment::STATUS_PENDING)
            ->where('payment.label', 'Pendente')
            ->where('payment.pix_code', 'SIMULATED_PIX_NOT_PAYABLE_payments_abc'));

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});
