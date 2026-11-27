<?php

use App\Services\Payments\GatewayException;
use App\Services\Payments\PagsmileClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'services.pagsmile.base_url' => 'https://gateway.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);
});

it('cria um pix e consulta pelo out trade no', function () {
    $this->travelTo('2026-11-27 16:26:00');

    Http::fake([
        'https://gateway.test/trade/pay' => Http::response([
            'code' => '10000',
            'msg' => 'Success',
            'trade_no' => '2022010110293900083',
            'out_trade_no' => 'pay-1',
            'trade_status' => 'PROCESSING',
            'qr_code' => '00020126580014br.gov.bcb.pix',
            'qr_code_url' => 'https://gateway.test/qr/pay-1',
            'qr_code_img' => 'data:image/png;base64,abc',
        ]),
        'https://gateway.test/trade/query' => Http::response([
            'code' => '10000',
            'msg' => 'Success',
            'trade_no' => '2022010110293900083',
            'out_trade_no' => 'pay-1',
            'trade_status' => 'SUCCESS',
            'order_currency' => 'BRL',
            'order_amount' => '49.90',
            'customer' => [
                'identification' => [
                    'number' => '12345678901',
                    'type' => 'CPF',
                ],
            ],
        ]),
    ]);

    $client = app(PagsmileClient::class);

    expect($client->amountForApi(1201))->toBe('12.01')
        ->and($client->amountForApi(4990))->toBe('49.90')
        ->and($client->amountForApi(10))->toBe('0.10')
        ->and($client->amountForApi(5_000_000))->toBe('50000.00')
        ->and($client->mapStatus('INITIAL'))->toBe('pending')
        ->and($client->mapStatus('REFUSED'))->toBe('failed')
        ->and($client->mapStatus('REFUSE_FAILED'))->toBe('failed')
        ->and($client->mapStatus('CANCEL'))->toBe('canceled')
        ->and($client->mapStatus('EXPIRED'))->toBe('expired');

    $created = $client->createPix(
        4990,
        'pay-1',
        str_repeat('á', 200),
        'user-1',
        'https://loja.test/webhooks/pagsmile',
        'Ana Lima',
        '12345678901',
        'ana@example.com',
    );
    $found = $client->findPayment('pay-1', '2022010110293900083');

    expect($created->id)->toBe('2022010110293900083')
        ->and($created->status)->toBe('pending')
        ->and($created->pixCode)->toBe('00020126580014br.gov.bcb.pix')
        ->and($created->qrCodeUrl)->toBe('https://gateway.test/qr/pay-1')
        ->and($created->qrCodeImg)->toBe('data:image/png;base64,abc')
        ->and($found->status)->toBe('paid')
        ->and($found->amountCents)->toBe(4990);

    $authorization = 'Basic '.base64_encode('app-id-teste:chave-secreta-teste');

    Http::assertSent(function ($request) use ($authorization): bool {
        if ($request->method() !== 'POST' || $request->url() !== 'https://gateway.test/trade/pay') {
            return false;
        }

        return $request['app_id'] === 'app-id-teste'
            && $request['method'] === 'PIX'
            && $request['out_trade_no'] === 'pay-1'
            && $request['order_amount'] === '49.90'
            && $request['order_currency'] === 'BRL'
            && $request['buyer_id'] === 'user-1'
            && $request['timestamp'] === '2026-11-27 16:26:00'
            && mb_strlen($request['subject']) === 128
            && $request['customer']['identify']['type'] === 'CPF'
            && $request['customer']['identify']['number'] === '12345678901'
            && $request->hasHeader('Authorization', $authorization);
    });

    Http::assertSent(function ($request): bool {
        if ($request->url() !== 'https://gateway.test/trade/query') {
            return false;
        }

        return $request['out_trade_no'] === 'pay-1'
            && $request['trade_no'] === '2022010110293900083';
    });
});

it('trata timeout sem vazar a chave', function () {
    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake(function () {
        throw new ConnectionException('cURL error 28');
    });

    try {
        app(PagsmileClient::class)->createPix(
            1000,
            'pay-timeout',
            'Livro',
            'user-1',
            'https://loja.test/webhooks/pagsmile',
            'Ana Lima',
            '12345678901',
        );
        expect(false)->toBeTrue();
    } catch (GatewayException $exception) {
        expect($exception->getMessage())->toBe('Não foi possível falar com o serviço de pagamento.')
            ->and($exception->getMessage())->not->toContain('chave-secreta-teste')
            ->and($exception->getMessage())->not->toContain('12345678901');
    }

    $log = implode("\n", $logged);

    expect($log)->not->toContain('chave-secreta-teste')
        ->and($log)->not->toContain('12345678901')
        ->and($log)->toContain('pagsmile.request_failed')
        ->and($log)->toContain('pay-timeout');
});

it('trata http 400 sem gravar o corpo nem a chave', function () {
    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake([
        'https://gateway.test/*' => Http::response([
            'code' => '40002',
            'msg' => 'chave-secreta-teste',
            'sub_msg' => 'out_trade_no is duplicate',
            'customer' => [
                'identify' => [
                    'number' => '12345678901',
                ],
            ],
        ], 400),
    ]);

    expect(fn () => app(PagsmileClient::class)->findPayment('pay-400'))
        ->toThrow(GatewayException::class);

    $log = implode("\n", $logged);

    expect($log)->not->toContain('chave-secreta-teste')
        ->and($log)->not->toContain('12345678901')
        ->and($log)->toContain('pagsmile.request_failed')
        ->and($log)->toContain('pay-400');

    Http::assertSentCount(1);
});

it('recusa credencial vazia sem chamar a pagsmile', function (string $key) {
    config(["services.pagsmile.{$key}" => '']);

    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake();

    expect(fn () => app(PagsmileClient::class)->createPix(
        1000,
        'pay-vazio',
        'Livro',
        'user-1',
        'https://loja.test/webhooks/pagsmile',
        'Ana Lima',
        '12345678901',
    ))->toThrow(GatewayException::class);

    Http::assertNothingSent();

    $log = implode("\n", $logged);

    expect($log)->not->toContain('chave-secreta-teste')
        ->and($log)->not->toContain('12345678901')
        ->and($log)->toContain('pagsmile.missing_credentials')
        ->and($log)->toContain($key);
})->with([
    'base_url',
    'app_id',
    'security_key',
]);

it('traduz status desconhecido para pending sem lançar erro', function () {
    Http::fake([
        'https://gateway.test/trade/pay' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-3',
            'trade_status' => 'DISPUTE',
        ]),
    ]);

    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->level.' '.$event->message.' '.json_encode($event->context);
    });

    $payment = app(PagsmileClient::class)->createPix(
        150,
        'pay-3',
        'Livro',
        'user-1',
        'https://loja.test/webhooks/pagsmile',
        'Ana Lima',
        '12345678901',
    );

    $log = implode("\n", $logged);

    expect($payment->status)->toBe('pending')
        ->and($log)->toContain('pagsmile.unknown_status')
        ->and($log)->not->toContain('chave-secreta-teste')
        ->and($log)->not->toContain('12345678901');
});

it('recusa code diferente de sucesso sem gravar a mensagem', function () {
    $logged = [];
    Log::listen(function ($event) use (&$logged): void {
        $logged[] = $event->message.' '.json_encode($event->context);
    });

    Http::fake([
        'https://gateway.test/*' => Http::response([
            'code' => '40002',
            'msg' => 'chave-secreta-teste',
            'trade_no' => 'trade-4',
            'trade_status' => 'PROCESSING',
            'customer' => [
                'identify' => [
                    'number' => '12345678901',
                ],
            ],
        ]),
    ]);

    expect(fn () => app(PagsmileClient::class)->findPayment('pay-code'))
        ->toThrow(GatewayException::class);

    $log = implode("\n", $logged);

    expect($log)->not->toContain('chave-secreta-teste')
        ->and($log)->not->toContain('12345678901')
        ->and($log)->toContain('pagsmile.request_failed')
        ->and($log)->toContain('trade-4');

    Http::assertSentCount(1);
});

it('recusa valor fora da faixa sem chamar a pagsmile', function () {
    Http::fake();

    expect(fn () => app(PagsmileClient::class)->amountForApi(9))
        ->toThrow(GatewayException::class);

    expect(fn () => app(PagsmileClient::class)->createPix(
        5_000_001,
        'pay-alto',
        'Livro',
        'user-1',
        'https://loja.test/webhooks/pagsmile',
        'Ana Lima',
        '12345678901',
    ))->toThrow(GatewayException::class);

    Http::assertNothingSent();
});
