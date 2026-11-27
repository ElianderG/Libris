<?php

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

it('abre o livro publicado pela slug com preço e só a prévia', function () {
    $this->get(route('books.show', 'a-janela-do-terceiro-andar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Book')
            ->where('book.slug', 'a-janela-do-terceiro-andar')
            ->where('book.author', 'Lena Vilar')
            ->where('book.price', 'R$ 37,90')
            ->where('book.category', 'Contos')
            ->missing('book.id')
            ->missing('book.content')
            ->where('book.preview', fn (string $preview): bool => str_contains($preview, 'não tinha cortina')
                && ! str_contains($preview, 'Na segunda semana')))
        ->assertDontSee('Na segunda semana, a senhora do casaco parou de aparecer');

    $this->get(route('books.show', 'o-mapa-que-cabia-no-bolso'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('book.title', 'O mapa que cabia no bolso')
            ->where('book.cover_url', null)
            ->where('book.price', 'R$ 25,00'));
});

it('responde 404 para slug inválida e livro não publicado', function () {
    $this->get(route('books.show', 'livro-que-nao-existe'))->assertNotFound();
    $this->get('/livros/1')->assertNotFound();
    $this->get(route('books.show', 'o-relogio-da-padaria'))->assertNotFound();
});
