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

it('consulta ao abrir e fecha o pedido quando o gateway responde pago', function () {
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
            ->missing('payment.gateway_id')
            ->missing('payment.simulated')
            ->where('payment.pix_code', '00020126580014br.gov.bcb.pix')
            ->where('payment.can_refresh', false)
            ->where('total', 'R$ 37,90')
            ->where('payment.created_at', $order->payments()->first()->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i')));

    $payment = $order->payments()->first();

    expect($payment->status)->toBe(Payment::STATUS_PAID)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->raw_response['pix_code'] ?? null)->toBe('00020126580014br.gov.bcb.pix')
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);

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
            ->where('payment.pix_code', null)
            ->where('payment.can_refresh', true)
            ->missing('notice'));

    $this->actingAs($order->user)
        ->post(route('payments.refresh', $order))
        ->assertRedirect(route('payments.show', $order));

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('payment.label', 'Pago'));

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PAID)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);

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
            ->where('payment.pix_code', null));

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

it('consulta a pagsmile ao abrir e mostra pago', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);

    Http::fake([
        'https://pagsmile.test/trade/query' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-39',
            'trade_status' => 'SUCCESS',
            'order_amount' => '37.90',
            'qr_code' => '00020126580014br.gov.bcb.pix',
        ]),
    ]);

    $order = pagsmileOrder();

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('payment.status', Payment::STATUS_PAID)
            ->where('payment.label', 'Pago')
            ->where('payment.pix_code', '00020126580014br.gov.bcb.pix')
            ->where('payment.can_refresh', false)
            ->where('total', 'R$ 37,90'));

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PAID)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://pagsmile.test/trade/query'
        && $request['out_trade_no'] === (string) $order->payments()->first()->id
        && $request['trade_no'] === 'trade-39');
});

it('não marca pago quando a consulta da pagsmile ainda está processando', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);

    Http::fake([
        'https://pagsmile.test/trade/query' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-39',
            'trade_status' => 'PROCESSING',
            'order_amount' => '37.90',
        ]),
    ]);

    $order = pagsmileOrder();

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('payment.status', Payment::STATUS_PENDING)
            ->where('payment.label', 'Pendente')
            ->where('payment.can_refresh', true));

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

it('mantém o status da pagsmile quando a consulta cai', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);

    Http::fake(function () {
        throw new ConnectionException('cURL error 28');
    });

    $order = pagsmileOrder();

    $this->actingAs($order->user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertSessionHas('error', 'Não foi possível falar com o serviço de pagamento.')
        ->assertInertia(fn ($page) => $page->where('payment.status', Payment::STATUS_PENDING));

    expect($order->payments()->first()->status)->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

function pagsmileOrder(): Order
{
    $order = orderWithPendingPayment();
    $order->gateway = Order::GATEWAY_PAGSMILE;
    $order->save();

    $payment = $order->payments()->first();
    $payment->gateway = Order::GATEWAY_PAGSMILE;
    $payment->gateway_id = 'trade-39';
    $payment->save();

    return $order->fresh();
}
