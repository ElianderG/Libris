<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagsmileClient
{
    public function createPix(
        int $amountCents,
        string $outTradeNo,
        string $subject,
        string $buyerId,
        string $notifyUrl,
        string $customerName,
        string $documentNumber,
        ?string $customerEmail = null,
    ): GatewayPayment {
        $outTradeNo = trim($outTradeNo);

        if ($outTradeNo === '') {
            $this->logFailure(null, null, null);

            throw new GatewayException;
        }

        $customer = [
            'name' => $customerName,
            'identify' => [
                'type' => 'CPF',
                'number' => $documentNumber,
            ],
        ];

        if (is_string($customerEmail) && trim($customerEmail) !== '') {
            $customer['email'] = trim($customerEmail);
        }

        $response = $this->send('/trade/pay', [
            'method' => 'PIX',
            'out_trade_no' => $outTradeNo,
            'notify_url' => $notifyUrl,
            'subject' => mb_substr(trim($subject), 0, 128),
            'order_amount' => $this->amountForApi($amountCents),
            'order_currency' => 'BRL',
            'buyer_id' => $buyerId,
            'customer' => $customer,
        ], $outTradeNo);

        return $this->paymentFrom($response, $outTradeNo);
    }

    public function findPayment(string $outTradeNo = '', ?string $tradeNo = null): GatewayPayment
    {
        $outTradeNo = trim($outTradeNo);
        $tradeNo = is_string($tradeNo) ? trim($tradeNo) : '';

        if ($outTradeNo === '' && $tradeNo === '') {
            $this->logFailure(null, null, null);

            throw new GatewayException;
        }

        $body = [];

        if ($outTradeNo !== '') {
            $body['out_trade_no'] = $outTradeNo;
        }

        if ($tradeNo !== '') {
            $body['trade_no'] = $tradeNo;
        }

        $response = $this->send(
            '/trade/query',
            $body,
            $outTradeNo !== '' ? $outTradeNo : null,
            $tradeNo !== '' ? $tradeNo : null,
        );

        return $this->paymentFrom($response, $outTradeNo !== '' ? $outTradeNo : null);
    }

    public function amountForApi(int $cents): string
    {
        if ($cents < 10 || $cents > 5_000_000) {
            Log::error('pagsmile.invalid_amount', [
                'status' => null,
                'out_trade_no' => null,
                'trade_no' => null,
            ]);

            throw new GatewayException;
        }

        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    public function mapStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));

        $mapped = match ($normalized) {
            'INITIAL', 'PROCESSING' => 'pending',
            'SUCCESS' => 'paid',
            'REFUSED', 'REFUSE_FAILED' => 'failed',
            'CANCEL' => 'canceled',
            'EXPIRED' => 'expired',
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        Log::warning('pagsmile.unknown_status', [
            'status' => mb_substr($normalized, 0, 40),
        ]);

        return 'pending';
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function send(string $path, array $body, ?string $outTradeNo, ?string $tradeNo = null): Response
    {
        [$baseUrl, $appId, $securityKey] = $this->credentials();

        $body['app_id'] = $appId;
        $body['timestamp'] = now()->format('Y-m-d H:i:s');

        try {
            $response = $this->request($baseUrl, $appId, $securityKey)->post($path, $body);
        } catch (ConnectionException) {
            $this->logFailure(null, $outTradeNo, $tradeNo);

            throw new GatewayException;
        }

        if ($response->failed()) {
            $this->logFailure($response->status(), $outTradeNo, $tradeNo ?? $this->tradeNoFrom($response));

            throw new GatewayException;
        }

        return $response;
    }

    private function paymentFrom(Response $response, ?string $outTradeNo): GatewayPayment
    {
        $payload = $response->json();
        $payload = is_array($payload) ? $payload : [];

        $tradeNo = $this->tradeNoFrom($response) ?? '';
        $status = isset($payload['trade_status']) && is_scalar($payload['trade_status'])
            ? trim((string) $payload['trade_status'])
            : '';
        $code = isset($payload['code']) && is_scalar($payload['code'])
            ? trim((string) $payload['code'])
            : '';

        if ($code !== '10000' || $tradeNo === '' || $status === '') {
            $this->logFailure($response->status(), $outTradeNo, $tradeNo !== '' ? $tradeNo : null);

            throw new GatewayException;
        }

        return new GatewayPayment(
            $tradeNo,
            $this->mapStatus($status),
            $this->stringField($payload, 'qr_code'),
            $this->amountCentsFrom($payload),
            $this->stringField($payload, 'qr_code_url'),
            $this->stringField($payload, 'qr_code_img'),
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function credentials(): array
    {
        $baseUrl = trim((string) config('services.pagsmile.base_url'));
        $appId = trim((string) config('services.pagsmile.app_id'));
        $securityKey = trim((string) config('services.pagsmile.security_key'));
        $missing = [];

        if ($baseUrl === '') {
            $missing[] = 'base_url';
        }

        if ($appId === '') {
            $missing[] = 'app_id';
        }

        if ($securityKey === '') {
            $missing[] = 'security_key';
        }

        if ($missing !== []) {
            Log::error('pagsmile.missing_credentials', [
                'status' => null,
                'out_trade_no' => null,
                'trade_no' => null,
                'missing' => $missing,
            ]);

            throw new GatewayException;
        }

        return [$baseUrl, $appId, $securityKey];
    }

    private function request(string $baseUrl, string $appId, string $securityKey): PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withBasicAuth($appId, $securityKey)
            ->acceptJson()
            ->asJson()
            ->timeout(10);
    }

    private function tradeNoFrom(Response $response): ?string
    {
        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['trade_no']) || ! is_string($payload['trade_no'])) {
            return null;
        }

        $tradeNo = trim($payload['trade_no']);

        return $tradeNo === '' ? null : $tradeNo;
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function stringField(array $payload, string $key): ?string
    {
        if (! isset($payload[$key]) || ! is_string($payload[$key])) {
            return null;
        }

        $value = trim($payload[$key]);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function amountCentsFrom(array $payload): ?int
    {
        if (! array_key_exists('order_amount', $payload)) {
            return null;
        }

        $amount = $payload['order_amount'];

        if (is_string($amount) && is_numeric($amount)) {
            $amount = (float) $amount;
        }

        if (! is_int($amount) && ! is_float($amount)) {
            return 0;
        }

        if (! is_finite($amount) || $amount <= 0) {
            return 0;
        }

        return (int) round($amount * 100);
    }

    private function logFailure(?int $status, ?string $outTradeNo, ?string $tradeNo): void
    {
        Log::error('pagsmile.request_failed', [
            'status' => $status,
            'out_trade_no' => $this->logId($outTradeNo),
            'trade_no' => $this->logId($tradeNo),
        ]);
    }

    private function logId(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 64);
    }
}
