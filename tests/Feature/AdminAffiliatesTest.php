<?php

use App\Models\AffiliateClick;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lista na home só os cinco pedidos pagos mais recentes', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $buyer = User::factory()->create();

    $paid = collect(range(1, 6))->map(function () use ($buyer) {
        $order = new Order;
        $order->user_id = $buyer->id;
        $order->status = Order::STATUS_PAID;
        $order->total_cents = 1000;
        $order->save();

        return $order;
    });

    $pending = new Order;
    $pending->user_id = $buyer->id;
    $pending->status = Order::STATUS_PENDING_PAYMENT;
    $pending->total_cents = 9000;
    $pending->save();

    $this->actingAs($admin)
        ->get(route('admin.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recent', 5)
            ->where('recent.0.id', $paid[5]->id)
            ->where('recent.4.id', $paid[1]->id)
            ->where('recent', fn ($recent) => collect($recent)->pluck('id')->doesntContain($pending->id)
                && collect($recent)->pluck('id')->doesntContain($paid[0]->id)));
});

it('mostra cliques, vendas pagas e comissão só de quem indicou', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $buyer = User::factory()->create(['name' => 'Comprador']);

    $active = User::factory()->create(['name' => 'Ana']);
    $clicksOnly = User::factory()->create(['name' => 'Caio']);
    User::factory()->create(['name' => 'Bruno']);

    $saleOnly = User::factory()->create(['name' => 'Davi']);

    foreach (['ana-1', 'ana-2'] as $token) {
        $click = new AffiliateClick;
        $click->affiliate_user_id = $active->id;
        $click->token = $token;
        $click->save();
    }

    $click = new AffiliateClick;
    $click->affiliate_user_id = $clicksOnly->id;
    $click->token = 'caio-1';
    $click->save();

    $paid = new Order;
    $paid->user_id = $buyer->id;
    $paid->status = Order::STATUS_PAID;
    $paid->total_cents = 3790;
    $paid->affiliate_id = $active->id;
    $paid->save();

    $pending = new Order;
    $pending->user_id = $buyer->id;
    $pending->status = Order::STATUS_PENDING_PAYMENT;
    $pending->total_cents = 2500;
    $pending->affiliate_id = $active->id;
    $pending->save();

    $ignored = new Order;
    $ignored->user_id = $buyer->id;
    $ignored->status = Order::STATUS_PAID;
    $ignored->total_cents = 5000;
    $ignored->affiliate_id = $saleOnly->id;
    $ignored->save();

    $payment = new Payment;
    $payment->order_id = $paid->id;
    $payment->amount_cents = $paid->total_cents;
    $payment->status = Payment::STATUS_PAID;
    $payment->method = Payment::METHOD_PIX;
    $payment->save();

    $confirmed = new Commission;
    $confirmed->affiliate_user_id = $active->id;
    $confirmed->order_id = $paid->id;
    $confirmed->payment_id = $payment->id;
    $confirmed->base_cents = 3790;
    $confirmed->percent = 10;
    $confirmed->amount_cents = 379;
    $confirmed->status = Commission::STATUS_CONFIRMED;
    $confirmed->save();

    $openPayment = new Payment;
    $openPayment->order_id = $pending->id;
    $openPayment->amount_cents = $pending->total_cents;
    $openPayment->status = Payment::STATUS_PENDING;
    $openPayment->method = Payment::METHOD_PIX;
    $openPayment->save();

    $open = new Commission;
    $open->affiliate_user_id = $active->id;
    $open->order_id = $pending->id;
    $open->payment_id = $openPayment->id;
    $open->base_cents = 2500;
    $open->percent = 10;
    $open->amount_cents = 250;
    $open->status = 'pending';
    $open->save();

    $this->actingAs($admin)
        ->get(route('admin.affiliates'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/affiliates/Index')
            ->where('affiliates', [
                [
                    'name' => 'Ana',
                    'code' => $active->affiliate_code,
                    'clicks' => 2,
                    'paid_orders' => 1,
                    'commission' => 'R$ 3,79',
                ],
                [
                    'name' => 'Caio',
                    'code' => $clicksOnly->affiliate_code,
                    'clicks' => 1,
                    'paid_orders' => 0,
                    'commission' => 'R$ 0,00',
                ],
            ]));
});
