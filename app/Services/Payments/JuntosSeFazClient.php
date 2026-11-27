<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JuntosSeFazClient
{
    public function createPayment(int $amountCents, ?string $reference = null): GatewayPayment
    {
        $body = [
            'amount' => $this->amountForApi($amountCents),
            'currency' => 'BRL',
            'method' => 'pix',
        ];

        if (is_string($reference) && $reference !== '') {
            $body['reference'] = $reference;
        }

        $response = $this->send('post', '/payments', $body);

        return $this->paymentFrom($response, null);
    }

    public function findPayment(string $externalId): GatewayPayment
    {
        $response = $this->send('get', '/payments/'.rawurlencode($externalId));

        return $this->paymentFrom($response, $externalId);
    }

    public function amountForApi(int $cents): float
    {
        if ($cents < 1) {
            Log::error('juntos_se_faz.invalid_amount', [
                'status' => null,
                'operation_id' => null,
            ]);

            throw new GatewayException;
        }

        return round($cents / 100, 2);
    }

    public function mapStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        $mapped = match ($normalized) {
            'pending' => 'pending',
            'approved', 'paid' => 'paid',
            'declined', 'failed' => 'failed',
            'canceled', 'cancelled' => 'canceled',
            'expired' => 'expired',
            default => null,
        };

        if ($mapped !== null) {
            return $mapped;
        }

        Log::warning('juntos_se_faz.unknown_status', [
            'status' => mb_substr($normalized, 0, 40),
        ]);

        return 'pending';
    }

    /**
     * @param  array<string, float|string>|null  $body
     */
    private function send(string $method, string $path, ?array $body = null): Response
    {
        $baseUrl = trim((string) config('services.juntos_se_faz.base_url'));

        if ($baseUrl === '') {
            Log::error('juntos_se_faz.missing_base_url', [
                'status' => null,
                'operation_id' => null,
            ]);

            throw new GatewayException;
        }

        try {
            $request = $this->request($baseUrl);

            $response = $method === 'post'
                ? $request->post($path, $body ?? [])
                : $request->get($path);
        } catch (ConnectionException) {
            $this->logFailure(null, null);

            throw new GatewayException;
        }

        if ($response->failed()) {
            $this->logFailure($response->status(), null);

            throw new GatewayException;
        }

        return $response;
    }

    private function paymentFrom(Response $response, ?string $operationId): GatewayPayment
    {
        $payload = $response->json();
        $id = is_array($payload) && isset($payload['id']) && is_string($payload['id']) && $payload['id'] !== ''
            ? $payload['id']
            : null;
        $status = is_array($payload) && isset($payload['status']) && is_scalar($payload['status'])
            ? trim((string) $payload['status'])
            : null;

        $pixCode = is_array($payload) && isset($payload['pix_code']) && is_string($payload['pix_code']) && $payload['pix_code'] !== ''
            ? $payload['pix_code']
            : null;

        if ($id === null || $status === null || $status === '') {
            $this->logFailure($response->status(), $id ?? $operationId);

            throw new GatewayException;
        }

        return new GatewayPayment($id, $this->mapStatus($status), $pixCode);
    }

    private function request(string $baseUrl): PendingRequest
    {
        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken((string) config('services.juntos_se_faz.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(10);
    }

    private function logFailure(?int $status, ?string $operationId): void
    {
        Log::error('juntos_se_faz.request_failed', [
            'status' => $status,
            'operation_id' => $operationId,
        ]);
    }
}
