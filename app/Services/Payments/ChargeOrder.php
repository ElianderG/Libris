<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChargeOrder
{
    public function __construct(
        private JuntosSeFazClient $juntos,
        private PagsmileClient $pagsmile,
    ) {}

    public function charge(Order $order, User $user, ?string $document = null): Payment
    {
        if ($order->user_id !== $user->id) {
            throw new RuntimeException('Esse pedido não pertence a esta conta.');
        }

        if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
            throw new RuntimeException('Esse pedido não pode ser cobrado.');
        }

        $gateway = $order->gateway === Order::GATEWAY_PAGSMILE
            ? Order::GATEWAY_PAGSMILE
            : Order::GATEWAY_JUNTOS;

        $payment = DB::transaction(function () use ($order, $gateway): Payment {
            Payment::query()
                ->where('order_id', $order->id)
                ->where('status', Payment::STATUS_PENDING)
                ->update(['status' => Payment::STATUS_CANCELED]);

            $payment = new Payment;
            $payment->order_id = $order->id;
            $payment->gateway = $gateway;
            $payment->amount_cents = $order->total_cents;
            $payment->status = Payment::STATUS_PENDING;
            $payment->method = Payment::METHOD_PIX;
            $payment->save();

            return $payment;
        });

        try {
            if ($gateway === Order::GATEWAY_PAGSMILE) {
                $this->chargePagsmile($payment, $order, $user, $document);
            } else {
                $this->chargeJuntos($payment, $order);
            }
        } catch (GatewayException) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->gateway_id = null;
            $payment->save();

            return $payment;
        }

        return $payment;
    }

    private function chargeJuntos(Payment $payment, Order $order): void
    {
        $payment->raw_request = [
            'amount' => $this->juntos->amountForApi($order->total_cents),
            'currency' => 'BRL',
            'method' => Payment::METHOD_PIX,
            'reference' => (string) $payment->id,
        ];
        $payment->save();

        $result = $this->juntos->createPayment($order->total_cents, (string) $payment->id);
        $payment->gateway_id = $result->id;
        $payment->status = $result->status;
        $payment->raw_response = [
            'id' => $result->id,
            'status' => $result->status,
            'pix_code' => $result->pixCode,
        ];
        $payment->save();
    }

    private function chargePagsmile(Payment $payment, Order $order, User $user, ?string $document): void
    {
        $digits = preg_replace('/\D/', '', (string) $document) ?? '';
        $amount = $this->pagsmile->amountForApi($order->total_cents);

        $payment->raw_request = [
            'method' => 'PIX',
            'amount' => $amount,
            'currency' => 'BRL',
            'out_trade_no' => (string) $payment->id,
        ];
        $payment->save();

        if (strlen($digits) !== 11) {
            throw new GatewayException;
        }

        $subject = $order->items()->orderBy('id')->value('title');

        $result = $this->pagsmile->createPix(
            $order->total_cents,
            (string) $payment->id,
            is_string($subject) ? $subject : '',
            (string) $user->id,
            url('/webhooks/pagsmile'),
            $user->name,
            $digits,
            $user->email,
        );

        $response = [
            'qr_code' => $result->pixCode,
        ];

        if (is_string($result->qrCodeUrl) && $result->qrCodeUrl !== '') {
            $response['qr_code_url'] = $result->qrCodeUrl;
        }

        if (is_string($result->qrCodeImg) && $result->qrCodeImg !== '') {
            $response['qr_code_img'] = $result->qrCodeImg;
        }

        $payment->gateway_id = $result->id;
        $payment->status = $result->status;
        $payment->raw_response = $response;
        $payment->save();
    }
}
