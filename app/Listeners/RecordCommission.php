<?php

namespace App\Listeners;

use App\Events\PaymentConfirmed;
use App\Models\Commission;
use App\Models\Order;

class RecordCommission
{
    public function handle(PaymentConfirmed $event): void
    {
        $payment = $event->payment->fresh();

        if ($payment === null || $payment->status !== $payment::STATUS_PAID) {
            return;
        }

        $order = $payment->order;

        if ($order->affiliate_id === null || $order->status !== Order::STATUS_PAID) {
            return;
        }

        if (Commission::query()->where('order_id', $order->id)->exists()) {
            return;
        }

        $percent = max(0, (int) config('libris.affiliate.commission_percent'));

        $commission = new Commission;
        $commission->affiliate_user_id = $order->affiliate_id;
        $commission->order_id = $order->id;
        $commission->payment_id = $payment->id;
        $commission->base_cents = $order->total_cents;
        $commission->percent = $percent;
        $commission->amount_cents = intdiv($order->total_cents * $percent, 100);
        $commission->status = Commission::STATUS_CONFIRMED;
        $commission->save();
    }
}
