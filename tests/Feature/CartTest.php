<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

it('soma dois livros diferentes e não duplica o mesmo', function () {
    $this->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar'])
        ->assertRedirect(route('cart'));

    $this->post(route('cart.store'), ['slug' => 'o-mapa-que-cabia-no-bolso'])
        ->assertRedirect(route('cart'));

    $this->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar'])
        ->assertRedirect(route('cart'));

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Cart')
            ->has('items', 2)
            ->where('total', 'R$ 62,90'));
});

it('recusa livro não publicado e id inventado', function () {
    $this->from(route('books.show', 'a-janela-do-terceiro-andar'))
        ->post(route('cart.store'), ['slug' => 'o-relogio-da-padaria'])
        ->assertRedirect(route('books.show', 'a-janela-do-terceiro-andar'))
        ->assertSessionHas('error');

    $this->post(route('cart.store'), ['slug' => '1', 'price' => '1'])
        ->assertSessionHas('error');

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('items', 0));
});

it('remove um livro e acompanha o preço vivo do banco', function () {
    $this->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);
    $this->post(route('cart.store'), ['slug' => 'o-mapa-que-cabia-no-bolso']);

    $this->delete(route('cart.destroy', 'o-mapa-que-cabia-no-bolso'))
        ->assertRedirect(route('cart'));

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('items.0.slug', 'a-janela-do-terceiro-andar')
            ->where('total', 'R$ 37,90'));

    $book = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();
    $book->price_cents = 1000;
    $book->save();

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page->where('total', 'R$ 10,00'));
});

it('leva o visitante ao login sem esvaziar a sacola', function () {
    $this->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->get(route('checkout'))
        ->assertRedirect(route('login'));

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page
            ->has('items', 1)
            ->where('total', 'R$ 37,90'));
});

it('não cria pedido só de abrir o checkout', function () {
    $this->actingAs(User::factory()->create());
    $this->post(route('cart.store'), ['slug' => 'a-janela-do-terceiro-andar']);

    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Checkout'));

    expect(Order::query()->count())->toBe(0);
});
