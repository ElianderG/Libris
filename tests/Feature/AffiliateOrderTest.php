<?php

use App\Models\AffiliateClick;
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
    config([
        'services.juntos_se_faz.base_url' => 'https://gateway.test',
        'services.juntos_se_faz.token' => 'token-secreto-teste',
        'libris.affiliate.cookie_days' => 7,
    ]);
    Http::fake([
        'https://gateway.test/payments' => Http::response([
            'id' => 'payments_abc',
            'status' => 'pending',
        ], 201),
    ]);
});

function clickFor(User $affiliate, ?string $token = null): AffiliateClick
{
    $click = new AffiliateClick;
    $click->affiliate_user_id = $affiliate->id;
    $click->token = $token ?? 'token-'.$affiliate->id;
    $click->save();

    return $click;
}

function buy(User $buyer, array $extra = [], ?string $cookie = null): Order
{
    test()->actingAs($buyer)
        ->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $request = test()->actingAs($buyer);

    if ($cookie !== null) {
        $request = $request->withCookie('libris_ref', $cookie);
    }

    $request->post(route('checkout.store'), [
        'name' => $buyer->name,
        'email' => $buyer->email,
        ...$extra,
    ]);

    return Order::query()->where('user_id', $buyer->id)->latest('id')->firstOrFail();
}

it('grava o afiliado do cookie de outra pessoa', function () {
    $buyer = User::factory()->create();
    $affiliate = User::factory()->create();
    $click = clickFor($affiliate);

    $order = buy($buyer, ['ref' => 'FORCADO'], $click->token);

    expect($order->affiliate_id)->toBe($affiliate->id)
        ->and($order->affiliate_code)->toBe($affiliate->affiliate_code);
});

it('grava afiliado nulo quando o cookie é do próprio comprador', function () {
    $buyer = User::factory()->create();
    $own = clickFor($buyer, 'token-proprio');

    $order = buy($buyer, [], $own->token);

    expect($order->affiliate_id)->toBeNull()
        ->and($order->affiliate_code)->toBeNull();
});

it('grava afiliado nulo quando o clique passou de 7 dias', function () {
    $buyer = User::factory()->create();
    $affiliate = User::factory()->create();
    $old = clickFor($affiliate, 'token-velho');
    $old->created_at = now()->subDays(8);
    $old->save();

    $order = buy($buyer, [], $old->token);

    expect($order->affiliate_id)->toBeNull()
        ->and($order->affiliate_code)->toBeNull();
});

it('ignora ref enviado no formulário', function () {
    $buyer = User::factory()->create();
    $affiliate = User::factory()->create();

    $order = buy($buyer, ['ref' => $affiliate->affiliate_code]);

    expect($order->affiliate_id)->toBeNull()
        ->and($order->affiliate_code)->toBeNull();
});
