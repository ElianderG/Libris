<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

class RefreshPayment
{
    public function __construct(private JuntosSeFazClient $gateway) {}

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

        $payment->status = $result->status;
        $raw = is_array($payment->raw_response) ? $payment->raw_response : [];
        $raw['id'] = $result->id;
        $raw['status'] = $result->status;

        if (is_string($result->pixCode) && $result->pixCode !== '') {
            $raw['pix_code'] = $result->pixCode;
        }

        $payment->raw_response = $raw;
        $payment->save();

        // Ticket 19 confirma o pedido aqui, uma única vez.

        return true;
    }
}
