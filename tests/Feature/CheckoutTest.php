<?php

use App\Models\Order;
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
            ->where('notice', 'Pagamento de teste, sem dinheiro real.')
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
