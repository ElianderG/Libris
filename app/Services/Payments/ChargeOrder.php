<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChargeOrder
{
    public function __construct(private JuntosSeFazClient $gateway) {}

    public function charge(Order $order, User $user): Payment
    {
        if ($order->user_id !== $user->id) {
            throw new RuntimeException('Esse pedido não pertence a esta conta.');
        }

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            throw new RuntimeException('Esse pedido não está aguardando pagamento.');
        }

        $payment = DB::transaction(function () use ($order): Payment {
            Payment::query()
                ->where('order_id', $order->id)
                ->where('status', Payment::STATUS_PENDING)
                ->update(['status' => Payment::STATUS_CANCELED]);

            $payment = new Payment;
            $payment->order_id = $order->id;
            $payment->amount_cents = $order->total_cents;
            $payment->status = Payment::STATUS_PENDING;
            $payment->method = Payment::METHOD_PIX;
            $payment->raw_request = [
                'amount' => $this->gateway->amountForApi($order->total_cents),
                'currency' => 'BRL',
                'method' => Payment::METHOD_PIX,
                'reference' => '',
            ];
            $payment->save();

            $payment->raw_request = [
                'amount' => $this->gateway->amountForApi($order->total_cents),
                'currency' => 'BRL',
                'method' => Payment::METHOD_PIX,
                'reference' => (string) $payment->id,
            ];
            $payment->save();

            return $payment;
        });

        try {
            $result = $this->gateway->createPayment($order->total_cents, (string) $payment->id);
        } catch (GatewayException) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->gateway_id = null;
            $payment->save();

            return $payment;
        }

        $payment->gateway_id = $result->id;
        $payment->status = $result->status;
        $payment->raw_response = [
            'id' => $result->id,
            'status' => $result->status,
            'pix_code' => $result->pixCode,
        ];
        $payment->save();

        return $payment;
    }
}
