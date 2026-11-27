<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function catalogAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

/**
 * @param  array<string, mixed>  $extra
 * @return array<string, mixed>
 */
function bookForm(Category $category, array $extra = []): array
{
    return [
        'category_id' => $category->id,
        'title' => 'Notas da tarde',
        'author' => 'Lena Vilar',
        'description' => 'Uma descrição curta.',
        'content' => 'Texto completo do livro.',
        'price' => '37,90',
        'is_published' => '1',
        ...$extra,
    ];
}

function forgetCover(?string $path): void
{
    if (! is_string($path) || $path === '') {
        return;
    }

    $full = public_path($path);

    if (is_file($full)) {
        unlink($full);
    }
}

it('recusa o cadastro para um cliente', function () {
    $customer = User::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['category_id' => $category->id]);

    $this->actingAs($customer)->get(route('admin.books'))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.books.create'))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.books.store'), bookForm($category))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.books.edit', $product))->assertForbidden();
    $this->actingAs($customer)->put(route('admin.books.update', $product), bookForm($category))->assertForbidden();
    $this->actingAs($customer)->delete(route('admin.books.destroy', $product))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.books.unpublish', $product))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.categories'))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.categories.store'), ['name' => 'Contos'])->assertForbidden();
    $this->actingAs($customer)->delete(route('admin.categories.destroy', $category))->assertForbidden();

    expect(Product::query()->count())->toBe(1)
        ->and(Category::query()->count())->toBe(1);
});

it('cadastra categoria e livro publicado que aparece na vitrine', function () {
    $admin = catalogAdmin();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), ['name' => 'Contos'])
        ->assertRedirect();

    $category = Category::query()->where('name', 'Contos')->first();

    expect($category)->not->toBeNull()
        ->and($category->slug)->toBe('contos');

    $this->actingAs($admin)
        ->post(route('admin.books.store'), bookForm($category))
        ->assertRedirect();

    $product = Product::query()->where('title', 'Notas da tarde')->first();

    expect($product)->not->toBeNull()
        ->and($product->price_cents)->toBe(3790)
        ->and($product->slug)->toBe('notas-da-tarde')
        ->and($product->is_published)->toBeTrue()
        ->and($product->content)->toBe('Texto completo do livro.');

    $this->actingAs($admin)
        ->get(route('admin.books.edit', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/books/Form')
            ->where('book.price', '37,90')
            ->where('book.purchased', false));

    $this->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.slug', 'notas-da-tarde')
            ->where('products.data.0.price', 'R$ 37,90'));
});

it('converte 37.90 em centavos e mantém o slug quando o título muda', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.books.store'), bookForm($category, ['price' => '37.90']))
        ->assertRedirect();

    $product = Product::query()->first();

    expect($product)->not->toBeNull()
        ->and($product->price_cents)->toBe(3790);

    $this->actingAs($admin)
        ->put(route('admin.books.update', $product), bookForm($category, [
            'title' => 'Outro título',
            'price' => '10,50',
        ]))
        ->assertRedirect(route('admin.books.edit', $product));

    expect($product->fresh()->title)->toBe('Outro título')
        ->and($product->fresh()->slug)->toBe('notas-da-tarde')
        ->and($product->fresh()->price_cents)->toBe(1050);
});

it('recusa preço zero e conteúdo vazio', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->from(route('admin.books.create'))
        ->post(route('admin.books.store'), bookForm($category, ['price' => '0']))
        ->assertRedirect(route('admin.books.create'))
        ->assertSessionHasErrors('price');

    $this->actingAs($admin)
        ->from(route('admin.books.create'))
        ->post(route('admin.books.store'), bookForm($category, ['content' => '   ']))
        ->assertSessionHasErrors('content');

    expect(Product::query()->count())->toBe(0);
});

it('despublica o livro comprado e continua a leitura', function () {
    $admin = catalogAdmin();
    $buyer = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin)->post(route('admin.books.store'), bookForm($category));

    $product = Product::query()->firstOrFail();
    $order = new Order;
    $order->user_id = $buyer->id;
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

    $this->actingAs($admin)
        ->from(route('admin.books.edit', $product))
        ->delete(route('admin.books.destroy', $product))
        ->assertRedirect(route('admin.books.edit', $product))
        ->assertSessionHas('error', 'Este livro já foi comprado. Despublique para tirá-lo da vitrine.');

    expect($product->fresh())->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.books.edit', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('book.purchased', true));

    $this->actingAs($admin)
        ->post(route('admin.books.unpublish', $product))
        ->assertRedirect()
        ->assertSessionHas('success', 'Livro despublicado.');

    expect($product->fresh()->is_published)->toBeFalse();

    $this->get(route('catalog'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products.data', 0));

    $this->actingAs($buyer)
        ->get(route('library.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('book.content', 'Texto completo do livro.'));
});

it('busca livro pelo título', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();

    $this->actingAs($admin)->post(route('admin.books.store'), bookForm($category));
    $this->actingAs($admin)->post(route('admin.books.store'), bookForm($category, [
        'title' => 'O mapa do bolso',
        'price' => '10,00',
    ]));

    $this->actingAs($admin)
        ->get(route('admin.books', ['q' => 'mapa']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('books', 1)
            ->where('books.0.title', 'O mapa do bolso'));
});

it('troca a capa e apaga o arquivo anterior', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();
    $paths = [];

    try {
        $this->actingAs($admin)
            ->post(route('admin.books.store'), bookForm($category, [
                'cover' => UploadedFile::fake()->image('capa.jpg', 40, 60),
            ]))
            ->assertRedirect();

        $product = Product::query()->firstOrFail();
        $paths[] = $product->cover_path;

        expect(is_file(public_path((string) $product->cover_path)))->toBeTrue();

        $this->actingAs($admin)
            ->post(route('admin.books.update', $product), bookForm($category, [
                '_method' => 'put',
                'cover' => UploadedFile::fake()->image('nova.jpg', 40, 60),
            ]))
            ->assertRedirect(route('admin.books.edit', $product));

        $product->refresh();
        $paths[] = $product->cover_path;

        expect($paths[0])->not->toBe($product->cover_path)
            ->and(is_file(public_path((string) $paths[0])))->toBeFalse()
            ->and(is_file(public_path((string) $product->cover_path)))->toBeTrue();
    } finally {
        foreach ($paths as $path) {
            forgetCover($path);
        }
    }
});

it('recusa capa maior que 2 MB e apaga livro sem compra junto com a capa', function () {
    $admin = catalogAdmin();
    $category = Category::factory()->create();
    $path = null;

    try {
        $this->actingAs($admin)
            ->from(route('admin.books.create'))
            ->post(route('admin.books.store'), bookForm($category, [
                'cover' => UploadedFile::fake()->image('grande.jpg')->size(3000),
            ]))
            ->assertRedirect(route('admin.books.create'))
            ->assertSessionHasErrors('cover');

        expect(Product::query()->count())->toBe(0);

        $this->actingAs($admin)
            ->post(route('admin.books.store'), bookForm($category, [
                'cover' => UploadedFile::fake()->image('capa.jpg', 40, 60),
                'is_published' => '0',
            ]))
            ->assertRedirect();

        $product = Product::query()->firstOrFail();
        $path = $product->cover_path;

        expect($product->is_published)->toBeFalse()
            ->and(is_file(public_path((string) $path)))->toBeTrue();

        $this->actingAs($admin)
            ->delete(route('admin.books.destroy', $product))
            ->assertRedirect(route('admin.books'))
            ->assertSessionHas('success', 'Livro apagado.');

        expect(Product::query()->find($product->id))->toBeNull()
            ->and(is_file(public_path((string) $path)))->toBeFalse();
    } finally {
        forgetCover($path);
    }
});

it('não apaga categoria que ainda tem livro e mantém o slug ao renomear', function () {
    $admin = catalogAdmin();

    $this->actingAs($admin)->post(route('admin.categories.store'), ['name' => 'Contos']);
    $category = Category::query()->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), ['name' => 'Contos urbanos'])
        ->assertRedirect(route('admin.categories.edit', $category));

    expect($category->fresh()->slug)->toBe('contos')
        ->and($category->fresh()->name)->toBe('Contos urbanos');

    $this->actingAs($admin)->post(route('admin.books.store'), bookForm($category));

    $this->actingAs($admin)
        ->from(route('admin.categories.edit', $category))
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.edit', $category))
        ->assertSessionHas('error', 'Esta categoria tem livros. Mova ou apague os livros antes de apagá-la.');

    expect($category->fresh())->not->toBeNull();

    $empty = Category::factory()->create(['name' => 'Vazia']);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $empty))
        ->assertRedirect(route('admin.categories'));

    expect($empty->fresh())->toBeNull();
});
