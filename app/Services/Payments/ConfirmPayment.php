<?php

namespace App\Services\Payments;

use App\Events\PaymentConfirmed;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmPayment
{
    public function confirm(Payment $payment, GatewayPayment $result): void
    {
        DB::transaction(function () use ($payment, $result): void {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->first();

            if ($locked === null || $locked->status === Payment::STATUS_PAID) {
                return;
            }

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->first();

            if ($order === null) {
                return;
            }

            if ($result->status === Payment::STATUS_PAID) {
                $this->markPaid($locked, $order, $result);

                return;
            }

            if ($result->status === Payment::STATUS_PENDING) {
                $this->remember($locked, $result);
                $locked->save();

                return;
            }

            if ($locked->status === Payment::STATUS_PENDING && in_array($result->status, [
                Payment::STATUS_FAILED,
                Payment::STATUS_CANCELED,
                Payment::STATUS_EXPIRED,
            ], true)) {
                $locked->status = $result->status;
                $this->remember($locked, $result);
                $locked->save();
            }
        });
    }

    private function markPaid(Payment $payment, Order $order, GatewayPayment $result): void
    {
        if ($result->amountCents !== null && $result->amountCents !== $payment->amount_cents) {
            Log::warning('juntos_se_faz.amount_mismatch', [
                'status' => $result->status,
                'operation_id' => $result->id,
            ]);

            return;
        }

        if ($order->status === Order::STATUS_PAID) {
            return;
        }

        $payment->status = Payment::STATUS_PAID;
        $payment->setAttribute('paid_at', now());
        $this->remember($payment, $result);
        $payment->save();

        $order->status = Order::STATUS_PAID;
        $order->save();

        PaymentConfirmed::dispatch($payment);
    }

    private function remember(Payment $payment, GatewayPayment $result): void
    {
        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];
        $raw['id'] = $result->id;
        $raw['status'] = $result->status;

        if (is_string($result->pixCode) && $result->pixCode !== '') {
            $raw['pix_code'] = $result->pixCode;
        }

        $payment->raw_response = $raw;
    }
}
