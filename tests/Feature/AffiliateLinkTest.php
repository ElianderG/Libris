<?php

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed(CatalogSeeder::class);
});

it('entrega o link do livro com o código de quem está logado', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($owner)
        ->get(route('books.show', 'a-janela-do-terceiro-andar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('referralUrl', rtrim((string) config('app.url'), '/').'/livros/a-janela-do-terceiro-andar?ref='.$owner->affiliate_code))
        ->assertDontSee($other->affiliate_code, false);
});

it('não mostra código de indicação para o visitante', function () {
    $owner = User::factory()->create();

    $this->get(route('books.show', 'a-janela-do-terceiro-andar'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('referralUrl', null))
        ->assertDontSee($owner->affiliate_code, false);
});

it('mostra o próprio código na conta e esconde a página de quem não entrou', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get(route('affiliate'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Affiliate')
            ->where('auth.user.affiliate_code', $owner->affiliate_code));

    auth()->logout();

    $this->get(route('affiliate'))
        ->assertRedirect(route('login'));
});
