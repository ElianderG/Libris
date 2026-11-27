<?php

use App\Services\Payments\GatewayException;
use App\Services\Payments\JuntosSeFazClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'services.juntos_se_faz.base_url' => 'https://gateway.test',
        'services.juntos_se_faz.token' => 'token-secreto-teste',
    ]);
});

it('cria um pix e consulta pelo id externo', function () {
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_0123456789abcdef',
            'status' => 'pending',
            'environment' => 'staging',
            'simulated' => true,
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_0123456789abcdef',
        ], 201),
        'https://gateway.test/payments/payments_0123456789abcdef' => Http::response([
            'id' => 'payments_0123456789abcdef',
            'status' => 'approved',
        ]),
    ]);

    $client = app(JuntosSeFazClient::class);

    expect($client->amountForApi(3990))->toBe(39.9)
        ->and($client->amountForApi(4990))->toBe(49.9);

    $created = $client->createPayment(4990, 'pedido-1');
    $found = $client->findPayment('payments_0123456789abcdef');

    expect($created->id)->toBe('payments_0123456789abcdef')
        ->and($created->status)->toBe('pending')
        ->and($created->pixCode)->toBe('SIMULATED_PIX_NOT_PAYABLE_payments_0123456789abcdef')
        ->and($found->status)->toBe('paid');

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }

        return $request->url() === 'https://gateway.test/payments'
            && $request['amount'] === 49.9
            && $request['currency'] === 'BRL'
            && $request['method'] === 'pix'
            && $request['reference'] === 'pedido-1'
            && $request->hasHeader('Authorization', 'Bearer token-secreto-teste');
    });
});

it('trata timeout sem vazar o token', function () {
    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake(function () {
        throw new ConnectionException('cURL error 28');
    });

    try {
        app(JuntosSeFazClient::class)->createPayment(1000);
        expect(false)->toBeTrue();
    } catch (GatewayException $exception) {
        expect($exception->getMessage())->toBe('Não foi possível falar com o serviço de pagamento.')
            ->and($exception->getMessage())->not->toContain('token-secreto-teste');
    }

    expect(implode("\n", $logged))->not->toContain('token-secreto-teste')
        ->and(implode("\n", $logged))->toContain('juntos_se_faz.request_failed');
});

it('trata http 422 sem gravar o corpo nem o token', function () {
    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake([
        'https://gateway.test/*' => Http::response([
            'message' => 'token-secreto-teste invalid',
        ], 422),
    ]);

    expect(fn () => app(JuntosSeFazClient::class)->findPayment('pay_2'))
        ->toThrow(GatewayException::class);

    expect(implode("\n", $logged))->not->toContain('token-secreto-teste')
        ->and(implode("\n", $logged))->toContain('juntos_se_faz.request_failed');

    Http::assertSentCount(1);
});

it('recusa a base url vazia sem chamar o gateway', function () {
    config(['services.juntos_se_faz.base_url' => '']);

    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake();

    expect(fn () => app(JuntosSeFazClient::class)->createPayment(100))
        ->toThrow(GatewayException::class);

    Http::assertNothingSent();
    expect(implode("\n", $logged))->not->toContain('token-secreto-teste')
        ->and(implode("\n", $logged))->toContain('juntos_se_faz.missing_base_url');
});

it('traduz status desconhecido para pending sem lançar erro', function () {
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'pay_3',
            'status' => 'processing',
        ]),
    ]);

    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->level.' '.$event->message;
    });

    $payment = app(JuntosSeFazClient::class)->createPayment(150);

    expect($payment->status)->toBe('pending')
        ->and(implode("\n", $logged))->toContain('juntos_se_faz.unknown_status')
        ->and(implode("\n", $logged))->not->toContain('token-secreto-teste');
});

it('simula aprovar, recusar, cancelar e expirar pelo id externo', function (string $action, string $gatewayStatus, string $internal) {
    Http::fake([
        'https://gateway.test/payments/pay_nova/'.$action => Http::response([
            'id' => 'pay_nova',
            'status' => $gatewayStatus,
        ]),
    ]);

    $payment = app(JuntosSeFazClient::class)->simulate('pay_nova', $action);

    expect($payment->status)->toBe($internal)
        ->and($payment->id)->toBe('pay_nova');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'https://gateway.test/payments/pay_nova/'.$action
        && $request->hasHeader('Authorization', 'Bearer token-secreto-teste'));
})->with([
    ['approve', 'approved', 'paid'],
    ['decline', 'declined', 'failed'],
    ['cancel', 'cancelled', 'canceled'],
    ['expire', 'expired', 'expired'],
]);

it('recusa uma ação desconhecida sem chamar o gateway', function () {
    Http::fake();

    expect(fn () => app(JuntosSeFazClient::class)->simulate('pay_nova', 'refund'))
        ->toThrow(GatewayException::class);

    Http::assertNothingSent();
});
