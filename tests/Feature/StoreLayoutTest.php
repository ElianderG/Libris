<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('abre a home da livraria', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('name', 'Libris te liberant'));
});

it('compartilha mensagens de sucesso e de erro', function () {
    $this->withSession([
        'success' => 'Livro guardado.',
        'error' => 'Não foi possível concluir.',
    ])->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('flash.success', 'Livro guardado.')
            ->where('flash.error', 'Não foi possível concluir.'));
});

it('aplica o modo escuro marrom quando o visitante escolhe', function () {
    $this->withUnencryptedCookie('appearance', 'dark')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('class="dark"', false);
});

it('mantém o modo claro quando o visitante escolhe', function () {
    $this->withUnencryptedCookie('appearance', 'light')
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('class="dark"', false);
});

it('abre o catálogo e a sacola vazios', function () {
    $this->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Catalog'));

    $this->get(route('cart'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Cart'));
});
