<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class RefreshPayment
{
    public function __construct(
        private JuntosSeFazClient $juntos,
        private PagsmileClient $pagsmile,
        private ConfirmPayment $confirmPayment,
    ) {}

    public function refresh(Order $order): bool
    {
        $payment = $order->payments()->latest('id')->first();

        if ($payment === null || $payment->status !== Payment::STATUS_PENDING) {
            return true;
        }

        if (! is_string($payment->gateway_id) || $payment->gateway_id === '') {
            return true;
        }

        try {
            $result = $payment->gateway === Order::GATEWAY_PAGSMILE
                ? $this->pagsmile->findPayment((string) $payment->id, $payment->gateway_id)
                : $this->juntos->findPayment($payment->gateway_id);
        } catch (GatewayException) {
            return false;
        }

        if (
            $payment->gateway === Order::GATEWAY_PAGSMILE
            && $result->status === Payment::STATUS_PAID
            && $result->amountCents !== $payment->amount_cents
        ) {
            Log::warning('pagsmile.amount_mismatch', [
                'trade_no' => mb_substr($result->id, 0, 64),
                'status' => $result->status,
            ]);

            return true;
        }

        $this->confirmPayment->confirm($payment, $result);

        return true;
    }
}
