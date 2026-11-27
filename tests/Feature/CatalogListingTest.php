<?php

use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

it('mostra só destaques publicados na home e não envia o texto do livro', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('featured', 4)
            ->has('recent', 4)
            ->where('featured', function ($books): bool {
                $books = collect($books);

                return $books->contains(fn ($book): bool => $book['price'] === 'R$ 37,90'
                    && $book['title'] === 'A janela do terceiro andar')
                    && $books->every(fn ($book): bool => ! array_key_exists('content', $book));
            }))
        ->assertDontSee('O relógio da padaria')
        ->assertDontSee('não tinha cortina');
});

it('esconde o livro não publicado e filtra categoria, texto e preço', function () {
    $this->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Catalog')
            ->has('products.data', 8)
            ->where('products.per_page', 12)
            ->where('products.total', 8))
        ->assertDontSee('O relógio da padaria')
        ->assertDontSee('não tinha cortina');

    $this->get(route('catalog', ['categoria' => 'ensaios', 'q' => 'mesa']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Notas sobre mesas vazias')
            ->where('products.data.0.price', 'R$ 9,90')
            ->where('filters.categoria', 'ensaios')
            ->where('filters.q', 'mesa'));

    $this->get(route('catalog', ['ordem' => 'preco']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.title', 'Notas sobre mesas vazias')
            ->where('products.data.7.title', 'Receitas para quem mora sozinho')
            ->where('filters.ordem', 'preco'));

    expect(Product::query()->where('slug', 'o-relogio-da-padaria')->value('is_published'))->toBeFalse();
});

it('devolve a estante vazia quando a categoria não existe', function () {
    $this->get(route('catalog', ['categoria' => 'inexistente']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 0));
});
