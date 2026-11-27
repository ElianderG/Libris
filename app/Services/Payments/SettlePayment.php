<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use RuntimeException;

class SettlePayment
{
    public function __construct(
        private JuntosSeFazClient $gateway,
        private ConfirmPayment $confirmPayment,
    ) {}

    public function settle(Payment $payment, string $action): void
    {
        $expected = match ($action) {
            'approve' => Payment::STATUS_PAID,
            'decline' => Payment::STATUS_FAILED,
            'cancel' => Payment::STATUS_CANCELED,
            'expire' => Payment::STATUS_EXPIRED,
            default => null,
        };

        if ($expected === null) {
            throw new RuntimeException('Não foi possível atualizar este pagamento.');
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            throw new RuntimeException('Essa tentativa não está pendente.');
        }

        $order = $payment->order;

        if ($order === null || $order->status !== Order::STATUS_PENDING_PAYMENT) {
            throw new RuntimeException('Esse pedido não está aguardando pagamento.');
        }

        if (! is_string($payment->gateway_id) || $payment->gateway_id === '') {
            throw new RuntimeException('Não foi possível atualizar este pagamento.');
        }

        $latestId = Payment::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->value('id');

        if ((int) $latestId !== $payment->id) {
            throw new RuntimeException('Só a tentativa mais recente pode ser alterada.');
        }

        try {
            $result = $this->gateway->simulate($payment->gateway_id, $action);
        } catch (GatewayException) {
            throw new RuntimeException('Não foi possível falar com o serviço de pagamento.');
        }

        $this->confirmPayment->confirm($payment, $result);

        if ($payment->fresh()?->status !== $expected) {
            throw new RuntimeException('Não foi possível atualizar este pagamento.');
        }
    }
}
