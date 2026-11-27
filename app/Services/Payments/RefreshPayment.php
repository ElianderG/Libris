<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

class RefreshPayment
{
    public function __construct(
        private JuntosSeFazClient $gateway,
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
            $result = $this->gateway->findPayment($payment->gateway_id);
        } catch (GatewayException) {
            return false;
        }

        $this->confirmPayment->confirm($payment, $result);

        return true;
    }
}
