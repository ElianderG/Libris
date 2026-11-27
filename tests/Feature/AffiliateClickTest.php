<?php

use App\Models\AffiliateClick;
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

it('grava o clique e o cookie de 7 dias ao abrir o livro', function () {
    $affiliate = User::factory()->create();
    $product = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();

    $response = $this->withHeader('User-Agent', str_repeat('a', 300))
        ->get(route('books.show', $product->slug).'?ref='.$affiliate->affiliate_code);

    $response->assertRedirect(route('books.show', $product->slug));

    $click = AffiliateClick::query()->first();
    $cookie = $response->getCookie('libris_ref');

    expect($click)->not->toBeNull()
        ->and($click->affiliate_user_id)->toBe($affiliate->id)
        ->and($click->product_id)->toBe($product->id)
        ->and($click->token)->toBe($cookie?->getValue())
        ->and(strlen((string) $click->user_agent))->toBe(255)
        ->and($cookie?->isHttpOnly())->toBeTrue()
        ->and($cookie?->getSameSite())->toBe('lax');

    $remainingDays = ($cookie->getExpiresTime() - time()) / 86400;

    expect($remainingDays)->toBeGreaterThan(6.9)
        ->and($remainingDays)->toBeLessThan(7.1);

    $this->withCookie('libris_ref', $click->token)
        ->get(route('books.show', $product->slug).'?ref='.$affiliate->affiliate_code)
        ->assertRedirect(route('books.show', $product->slug));

    expect(AffiliateClick::query()->count())->toBe(1);
});

it('ignora ref inválido e não apaga um cookie bom', function () {
    $affiliate = User::factory()->create();
    $product = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();

    $this->get(route('books.show', $product->slug).'?ref=NAOEXISTE')
        ->assertOk();

    expect(AffiliateClick::query()->count())->toBe(0);

    $this->get(route('books.show', $product->slug).'?ref='.$affiliate->affiliate_code);
    $token = AffiliateClick::query()->value('token');

    $this->withCookie('libris_ref', (string) $token)
        ->get(route('books.show', $product->slug).'?ref=NAOEXISTE')
        ->assertOk()
        ->assertCookieMissing('libris_ref');

    expect(AffiliateClick::query()->count())->toBe(1);
});

it('troca o cookie quando chega outro ref válido', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $slug = 'a-janela-do-terceiro-andar';

    $this->get(route('books.show', $slug).'?ref='.$first->affiliate_code);
    $this->get(route('books.show', $slug).'?ref='.$second->affiliate_code)
        ->assertRedirect(route('books.show', $slug));

    expect(AffiliateClick::query()->count())->toBe(2)
        ->and(AffiliateClick::query()->latest('id')->value('affiliate_user_id'))->toBe($second->id);
});

it('não grava clique do próprio código', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->get(route('books.show', 'a-janela-do-terceiro-andar').'?ref='.$owner->affiliate_code)
        ->assertOk()
        ->assertCookieMissing('libris_ref');

    expect(AffiliateClick::query()->count())->toBe(0);
});
