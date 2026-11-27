<?php

use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\ConfirmPayment;
use App\Services\Payments\GatewayPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function payableOrder(User $buyer, ?User $affiliate, int $totalCents = 3790): Payment
{
    $order = new Order;
    $order->user_id = $buyer->id;
    $order->status = Order::STATUS_PENDING_PAYMENT;
    $order->total_cents = $totalCents;
    $order->affiliate_id = $affiliate?->id;
    $order->affiliate_code = $affiliate?->affiliate_code;
    $order->save();

    $payment = new Payment;
    $payment->order_id = $order->id;
    $payment->amount_cents = $totalCents;
    $payment->status = Payment::STATUS_PENDING;
    $payment->method = Payment::METHOD_PIX;
    $payment->gateway_id = 'payments_comissao';
    $payment->save();

    return $payment;
}

it('gera uma comissão com o percentual configurado e não repete', function () {
    config(['libris.affiliate.commission_percent' => 10]);

    $buyer = User::factory()->create();
    $affiliate = User::factory()->create();
    $payment = payableOrder($buyer, $affiliate);
    $service = app(ConfirmPayment::class);
    $paid = new GatewayPayment('payments_comissao', Payment::STATUS_PAID, null, 3790);

    $service->confirm($payment, $paid);
    $service->confirm($payment->fresh(), $paid);

    $commission = Commission::query()->first();

    expect(Commission::query()->count())->toBe(1)
        ->and($commission)->not->toBeNull()
        ->and($commission->affiliate_user_id)->toBe($affiliate->id)
        ->and($commission->base_cents)->toBe(3790)
        ->and($commission->percent)->toBe(10)
        ->and($commission->amount_cents)->toBe(379)
        ->and($commission->status)->toBe(Commission::STATUS_CONFIRMED)
        ->and($payment->order->fresh()->total_cents)->toBe(3790);

    $this->actingAs($affiliate)
        ->get(route('affiliate'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Affiliate')
            ->has('commissions', 1)
            ->where('commissions.0.order_id', $payment->order_id)
            ->where('commissions.0.base', 'R$ 37,90')
            ->where('commissions.0.amount', 'R$ 3,79'));
});

it('não gera comissão sem afiliado', function () {
    $buyer = User::factory()->create();
    $payment = payableOrder($buyer, null);

    app(ConfirmPayment::class)->confirm(
        $payment,
        new GatewayPayment('payments_comissao', Payment::STATUS_PAID, null, 3790),
    );

    expect(Commission::query()->count())->toBe(0);
});
