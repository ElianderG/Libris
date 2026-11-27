<?php

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\PlaceOrder;
use App\Support\Cart;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

function placeBook(User $user, string $slug): Order
{
    $cart = app(Cart::class);
    $cart->clear();
    $cart->add($slug);

    return app(PlaceOrder::class)->place($user);
}

it('mostra só os pedidos de quem está logado, do mais novo para o mais antigo', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    placeBook($owner, 'a-janela-do-terceiro-andar');
    $newer = placeBook($owner, 'o-mapa-que-cabia-no-bolso');
    placeBook($other, 'notas-sobre-mesas-vazias');

    $this->actingAs($owner)
        ->get(route('purchases'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Purchases')
            ->has('orders', 2)
            ->where('orders.0.titles.0', 'O mapa que cabia no bolso')
            ->where('orders.0.total', 'R$ 25,00')
            ->where('orders.0.status', Order::STATUS_PENDING_PAYMENT)
            ->where('orders.0.status_label', 'Aguardando pagamento')
            ->where('orders.0.pix_url', route('payments.show', $newer))
            ->where('orders.1.titles.0', 'A janela do terceiro andar')
            ->where('orders.1.total', 'R$ 37,90')
            ->missing('orders.2'));

    $this->actingAs($other)
        ->get(route('purchases'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('orders', 1)
            ->where('orders.0.titles.0', 'Notas sobre mesas vazias'));
});

it('explica a lista vazia e manda o visitante ao login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('purchases'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Purchases')
            ->has('orders', 0));

    auth()->logout();

    $this->get(route('purchases'))
        ->assertRedirect(route('login'));
});
