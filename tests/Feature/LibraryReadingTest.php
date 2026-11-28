<?php

use App\Models\Order;
use App\Models\OrderItem;
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

function giveBook(User $user, Product $product): void
{
    $order = new Order;
    $order->user_id = $user->id;
    $order->status = Order::STATUS_PAID;
    $order->total_cents = $product->price_cents;
    $order->save();

    $item = new OrderItem;
    $item->order_id = $order->id;
    $item->product_id = $product->id;
    $item->title = $product->title;
    $item->unit_price_cents = $product->price_cents;
    $item->quantity = 1;
    $item->save();
}

it('mostra o livro pago uma vez e abre o texto inteiro', function () {
    $owner = User::factory()->create();
    $product = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();
    $product->content = "Primeiro parágrafo.\n\n<script>alert(1)</script>\n\nNa segunda semana, a senhora do casaco parou de aparecer.";
    $product->is_published = false;
    $product->save();

    giveBook($owner, $product);
    giveBook($owner, $product);

    $this->actingAs($owner)
        ->get(route('library'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Library')
            ->has('books', 1)
            ->where('books.0.slug', 'a-janela-do-terceiro-andar')
            ->where('books.0.read_url', route('library.show', 'a-janela-do-terceiro-andar')));

    $this->actingAs($owner)
        ->get(route('library.show', 'a-janela-do-terceiro-andar'))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertInertia(fn ($page) => $page
            ->component('LibraryBook')
            ->where('book.title', 'A janela do terceiro andar')
            ->where('book.content', $product->content));
});

it('nega a leitura a quem não comprou e a slug inexistente', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::query()->where('slug', 'o-mapa-que-cabia-no-bolso')->firstOrFail();
    giveBook($owner, $product);

    $this->get(route('library.show', $product->slug))->assertRedirect(route('login'));

    $this->actingAs($other)
        ->get(route('library.show', $product->slug))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('library.show', 'livro-que-nao-existe'))
        ->assertNotFound();

    $pending = new Order;
    $pending->user_id = $other->id;
    $pending->status = Order::STATUS_PENDING_PAYMENT;
    $pending->total_cents = $product->price_cents;
    $pending->save();

    $item = new OrderItem;
    $item->order_id = $pending->id;
    $item->product_id = $product->id;
    $item->title = $product->title;
    $item->unit_price_cents = $product->price_cents;
    $item->quantity = 1;
    $item->save();

    $this->actingAs($other)
        ->get(route('library'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('books', 0));
});

it('nega a leitura ao administrador que não comprou', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $product = Product::query()->where('slug', 'a-janela-do-terceiro-andar')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('library.show', $product->slug))
        ->assertForbidden();
});
