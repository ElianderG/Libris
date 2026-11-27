<?php

use App\Events\PaymentConfirmed;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Services\Payments\ConfirmPayment;
use App\Services\Payments\GatewayPayment;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

function orderAwaitingConfirmation(): Order
{
    $user = User::factory()->create();
    app(Cart::class)->add('a-janela-do-terceiro-andar');
    $order = app(PlaceOrder::class)->place($user);

    $payment = new Payment;
    $payment->order_id = $order->id;
    $payment->gateway_id = 'payments_abc';
    $payment->amount_cents = $order->total_cents;
    $payment->status = Payment::STATUS_PENDING;
    $payment->method = Payment::METHOD_PIX;
    $payment->save();

    return $order->fresh();
}

it('confirma uma vez e não muda o paid_at na segunda chamada', function () {
    Event::fake([PaymentConfirmed::class]);

    $order = orderAwaitingConfirmation();
    $payment = $order->payments()->first();
    $service = app(ConfirmPayment::class);
    $paid = new GatewayPayment('payments_abc', Payment::STATUS_PAID, null, 3790);

    $service->confirm($payment, $paid);
    $paidAt = $payment->fresh()->paid_at;

    $service->confirm($payment->fresh(), $paid);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PAID)
        ->and($payment->fresh()->paid_at?->equalTo($paidAt))->toBeTrue()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PAID);

    Event::assertDispatchedTimes(PaymentConfirmed::class, 1);
});

it('não marca pago quando o valor do gateway diverge', function () {
    Event::fake([PaymentConfirmed::class]);
    Log::spy();

    $order = orderAwaitingConfirmation();
    $payment = $order->payments()->first();

    app(ConfirmPayment::class)->confirm(
        $payment,
        new GatewayPayment('payments_abc', Payment::STATUS_PAID, null, 100),
    );

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->fresh()->paid_at)->toBeNull()
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Event::assertNotDispatched(PaymentConfirmed::class);
    Log::shouldHaveReceived('warning')->once();
});

it('marca só o pagamento quando a consulta falha de vez', function () {
    Event::fake([PaymentConfirmed::class]);

    $order = orderAwaitingConfirmation();
    $payment = $order->payments()->first();

    app(ConfirmPayment::class)->confirm(
        $payment,
        new GatewayPayment('payments_abc', Payment::STATUS_FAILED),
    );

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);

    Event::assertNotDispatched(PaymentConfirmed::class);
});
