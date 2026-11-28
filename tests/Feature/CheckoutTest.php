<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

it('devolve o visitante ao checkout com a mesma sacola', function () {
    $user = User::factory()->create();

    $this->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->get(route('checkout'))
        ->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('checkout'));

    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Checkout')
            ->where('items.0.title', 'A janela do terceiro andar')
            ->where('total', 'R$ 37,90')
            ->missing('notice')
            ->where('payer.email', $user->email));
});

it('cria um pedido pending_payment e recusa o segundo envio imediato', function () {
    config([
        'services.juntos_se_faz.base_url' => 'https://gateway.test',
        'services.juntos_se_faz.token' => 'token-secreto-teste',
    ]);
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_abc',
            'status' => 'pending',
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_abc',
        ], 201),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->actingAs($user)
        ->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
        ])
        ->assertRedirect();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->status)->toBe(Order::STATUS_PENDING_PAYMENT)
        ->and($order->user_id)->toBe($user->id)
        ->and($order->total_cents)->toBe(3790);

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->actingAs($user)
        ->from(route('checkout'))
        ->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
        ])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error');

    expect(Order::query()->count())->toBe(1);
});

it('ignora preço enviado no checkout e cobra o valor do banco', function () {
    config([
        'services.juntos_se_faz.base_url' => 'https://gateway.test',
        'services.juntos_se_faz.token' => 'token-secreto-teste',
    ]);
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_abc',
            'status' => 'pending',
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_abc',
        ], 201),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->actingAs($user)
        ->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
            'price' => '0,01',
            'total' => '0,01',
            'total_cents' => 1,
        ])
        ->assertRedirect();

    expect(Order::query()->value('total_cents'))->toBe(3790)
        ->and(Payment::query()->value('amount_cents'))->toBe(3790);
});

it('remove um livro no checkout e esvazia a sacola', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);
    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'o-mapa-que-cabia-no-bolso']);

    $this->actingAs($user)
        ->from(route('checkout'))
        ->post('/carrinho/o-mapa-que-cabia-no-bolso?_method=DELETE')
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('success', 'Livro removido da sacola.');

    $this->actingAs($user)
        ->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Checkout')
            ->has('items', 1)
            ->where('items.0.slug', 'a-janela-do-terceiro-andar')
            ->where('total', 'R$ 37,90'));

    $this->actingAs($user)
        ->post('/carrinho?_method=DELETE')
        ->assertRedirect(route('cart'))
        ->assertSessionHas('success', 'Sacola esvaziada.');

    $this->actingAs($user)
        ->get(route('cart'))
        ->assertInertia(fn ($page) => $page->has('items', 0));

    $this->actingAs($user)
        ->get(route('checkout'))
        ->assertRedirect(route('cart'));
});

it('remover o único livro no checkout volta para a sacola vazia', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->actingAs($user)
        ->from(route('checkout'))
        ->post('/carrinho/a-janela-do-terceiro-andar?_method=DELETE')
        ->assertRedirect(route('checkout'));

    $this->actingAs($user)
        ->get(route('checkout'))
        ->assertRedirect(route('cart'));

    $this->actingAs($user)
        ->get(route('cart'))
        ->assertInertia(fn ($page) => $page->has('items', 0));
});

it('escolhe a pagsmile e recusa cpf curto sem chamar o sandbox', function () {
    config([
        'services.pagsmile.base_url' => 'https://pagsmile.test',
        'services.pagsmile.app_id' => 'app-id-teste',
        'services.pagsmile.security_key' => 'chave-secreta-teste',
    ]);
    Http::fake([
        'https://pagsmile.test/trade/pay' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-38',
            'trade_status' => 'PROCESSING',
            'qr_code' => '00020126580014br.gov.bcb.pix',
            'qr_code_url' => 'https://pagsmile.test/qr/38',
        ]),
        'https://pagsmile.test/trade/query' => Http::response([
            'code' => '10000',
            'trade_no' => 'trade-38',
            'trade_status' => 'PROCESSING',
            'order_amount' => '37.90',
            'qr_code' => '00020126580014br.gov.bcb.pix',
            'qr_code_url' => 'https://pagsmile.test/qr/38',
        ]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->actingAs($user)
        ->from(route('checkout'))
        ->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
            'gateway' => 'pagsmile',
            'document' => '123',
        ])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('document');

    expect(Order::query()->count())->toBe(0);
    Http::assertNothingSent();

    $this->actingAs($user)
        ->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
            'gateway' => 'pagsmile',
            'document' => '123.456.789-01',
        ])
        ->assertRedirect();

    $order = Order::query()->first();
    $payment = Payment::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->gateway)->toBe(Order::GATEWAY_PAGSMILE)
        ->and($payment->gateway)->toBe(Order::GATEWAY_PAGSMILE)
        ->and($payment->gateway_id)->toBe('trade-38')
        ->and(json_encode($payment->raw_request))->not->toContain('12345678901');

    $this->actingAs($user)
        ->get(route('payments.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('asksDocument', true)
            ->where('payment.pix_code', '00020126580014br.gov.bcb.pix')
            ->where('payment.qr_image', 'https://pagsmile.test/qr/38'));
});

it('trata gateway desconhecido como juntos e ignora o cpf', function () {
    config([
        'services.juntos_se_faz.base_url' => 'https://gateway.test',
        'services.juntos_se_faz.token' => 'token-secreto-teste',
    ]);
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_juntos',
            'status' => 'pending',
            'pix_code' => 'SIMULATED_PIX_NOT_PAYABLE_payments_juntos',
        ], 201),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->actingAs($user)
        ->post(route('checkout.store'), [
            'name' => $user->name,
            'email' => $user->email,
            'gateway' => 'cartao',
            'document' => '12345678901',
        ])
        ->assertRedirect();

    $order = Order::query()->first();

    expect($order->gateway)->toBe(Order::GATEWAY_JUNTOS)
        ->and(json_encode($order->payments()->first()->raw_request))->not->toContain('12345678901');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/payments')
        && ! str_contains($request->url(), 'trade/pay'));
});

it('não grava pedido quando o nome está vazio', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.store'), ['slug' => 'o-mapa-que-cabia-no-bolso']);

    $this->actingAs($user)
        ->from(route('checkout'))
        ->post(route('checkout.store'), [
            'name' => '',
            'email' => $user->email,
        ])
        ->assertRedirect(route('checkout'))
        ->assertSessionHasErrors('name');

    expect(Order::query()->count())->toBe(0);

    $this->actingAs($user)
        ->get(route('checkout'))
        ->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.title', 'O mapa que cabia no bolso'));
});
