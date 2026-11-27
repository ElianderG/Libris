<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class ReceivePagsmileNotice
{
    public function __construct(
        private PagsmileClient $pagsmile,
        private ConfirmPayment $confirmPayment,
    ) {}

    public function receive(string $rawBody, string $signature): int
    {
        if (! $this->signatureMatches($signature, $rawBody)) {
            return 401;
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            return 200;
        }

        $tradeNo = $this->text($payload, 'trade_no');
        $tradeStatus = $this->text($payload, 'trade_status');

        Log::info('pagsmile.notification', [
            'trade_no' => $this->logValue($tradeNo),
            'status' => $this->logValue($tradeStatus),
        ]);

        $outTradeNo = $this->text($payload, 'out_trade_no');

        if ($outTradeNo === '' || ! ctype_digit($outTradeNo) || $tradeStatus === '') {
            return 200;
        }

        $payment = Payment::query()->find($outTradeNo);

        if ($payment === null || $payment->gateway !== Order::GATEWAY_PAGSMILE) {
            return 200;
        }

        $mapped = $this->pagsmile->mapStatus($tradeStatus);

        if ($payment->status === Payment::STATUS_PAID || $payment->status === $mapped) {
            return 200;
        }

        if ($mapped !== Payment::STATUS_PAID) {
            $this->confirmPayment->confirm($payment, new GatewayPayment(
                $tradeNo !== '' ? $tradeNo : (string) $payment->gateway_id,
                $mapped,
            ));

            return 200;
        }

        if ($this->cents($payload['amount'] ?? null) !== $payment->amount_cents) {
            Log::warning('pagsmile.amount_mismatch', [
                'trade_no' => $this->logValue($tradeNo),
                'status' => $mapped,
            ]);

            return 200;
        }

        try {
            $result = $this->pagsmile->findPayment((string) $payment->id, $payment->gateway_id);
        } catch (GatewayException) {
            return 503;
        }

        if ($result->status !== Payment::STATUS_PAID || $result->amountCents !== $payment->amount_cents) {
            if ($result->status !== Payment::STATUS_PAID && $result->status !== Payment::STATUS_PENDING) {
                $this->confirmPayment->confirm($payment, $result);
            }

            if ($result->status === Payment::STATUS_PAID) {
                Log::warning('pagsmile.amount_mismatch', [
                    'trade_no' => $this->logValue($result->id),
                    'status' => $result->status,
                ]);
            }

            return 200;
        }

        $this->confirmPayment->confirm($payment, $result);

        return 200;
    }

    private function signatureMatches(string $header, string $rawBody): bool
    {
        $secret = trim((string) config('services.pagsmile.security_key'));

        if ($secret === '' || trim($header) === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $header) as $element) {
            $pair = explode('=', trim($element), 2);

            if (count($pair) !== 2) {
                continue;
            }

            $parts[trim($pair[0])] = trim($pair[1]);
        }

        $timestamp = $parts['t'] ?? '';
        $given = strtolower($parts['v2'] ?? '');

        if ($timestamp === '' || $given === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $given);
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function text(array $payload, string $key): string
    {
        if (! isset($payload[$key]) || ! is_scalar($payload[$key])) {
            return '';
        }

        return trim((string) $payload[$key]);
    }

    private function cents(mixed $amount): ?int
    {
        if (is_string($amount) && is_numeric($amount)) {
            $amount = (float) $amount;
        }

        if (! is_int($amount) && ! is_float($amount)) {
            return null;
        }

        if (! is_finite($amount) || $amount <= 0) {
            return null;
        }

        return (int) round($amount * 100);
    }

    private function logValue(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 64);
    }
}
