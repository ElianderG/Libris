<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductRules;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('relaciona o livro com a categoria e guarda o preço em centavos', function () {
    $category = Category::factory()->create(['name' => 'Ensaio']);

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'title' => 'Notas de leitura',
        'price_cents' => 4990,
    ]);

    expect($product->category->is($category))->toBeTrue()
        ->and($category->products()->count())->toBe(1)
        ->and($product->price_cents)->toBe(4990)
        ->and($product->slug)->toBe('notas-de-leitura')
        ->and($category->slug)->toBe('ensaio');
});

it('recusa preço zero, negativo ou com centavos quebrados', function () {
    $rules = ['price_cents' => ProductRules::rules()['price_cents']];

    expect(validator(['price_cents' => 0], $rules)->fails())->toBeTrue()
        ->and(validator(['price_cents' => -10], $rules)->fails())->toBeTrue()
        ->and(validator(['price_cents' => 49.9], $rules)->fails())->toBeTrue()
        ->and(validator(['price_cents' => '49.90'], $rules)->fails())->toBeTrue()
        ->and(validator(['price_cents' => 4990], $rules)->fails())->toBeFalse();

    $product = new Product;

    expect(fn () => $product->price_cents = 0)->toThrow(ValidationException::class)
        ->and(fn () => $product->price_cents = -1)->toThrow(ValidationException::class)
        ->and(fn () => $product->price_cents = 10.5)->toThrow(ValidationException::class);

    $product->price_cents = '4990';

    expect($product->price_cents)->toBe(4990);
});

it('não grava dois slugs iguais e não troca o slug quando o título muda', function () {
    $first = Category::factory()->create(['name' => 'Romance']);
    $second = Category::factory()->create(['name' => 'Romance']);

    expect($second->slug)->toBe('romance-2')
        ->and($second->slug)->not->toBe($first->slug);

    $duplicate = new Category(['name' => 'Outra estante']);
    $duplicate->slug = $first->slug;

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);

    $product = Product::factory()->create(['title' => 'Notas de leitura']);
    $product->title = 'Outro título';
    $product->save();

    expect($product->fresh()->slug)->toBe('notas-de-leitura');
});

it('impede apagar categoria que ainda tem livro', function () {
    $category = Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    expect(fn () => $category->delete())->toThrow(QueryException::class)
        ->and($category->fresh())->not->toBeNull();

    $empty = Category::factory()->create();
    $empty->delete();

    expect($empty->fresh())->toBeNull();
});

it('ignora preço, publicação e slug vindos da atribuição em massa', function () {
    $category = Category::factory()->create();

    $product = new Product([
        'category_id' => $category->id,
        'title' => 'O livro',
        'author' => 'Autora',
        'description' => 'Uma história.',
        'content' => 'Texto longo.',
        'price_cents' => 1,
        'is_published' => true,
        'is_featured' => true,
        'slug' => 'forjado',
    ]);

    expect($product->is_published)->toBeFalse()
        ->and($product->is_featured)->toBeFalse()
        ->and($product->getAttributes())->not->toHaveKey('slug')
        ->and($product->getAttributes())->not->toHaveKey('price_cents');

    $product->price_cents = 4990;
    $product->save();

    expect($product->fresh()->slug)->toBe('o-livro')
        ->and($product->is_published)->toBeFalse()
        ->and($product->price_cents)->toBe(4990);
});

it('só aceita capa como caminho relativo dentro de covers', function () {
    $product = new Product;

    expect(fn () => $product->cover_path = 'https://evil.test/capa.webp')->toThrow(ValidationException::class)
        ->and(fn () => $product->cover_path = '/etc/passwd')->toThrow(ValidationException::class)
        ->and(fn () => $product->cover_path = 'covers/../../.env')->toThrow(ValidationException::class)
        ->and(fn () => $product->cover_path = '../covers/capa.webp')->toThrow(ValidationException::class);

    $product->cover_path = 'covers/capa.webp';

    expect($product->cover_path)->toBe('covers/capa.webp');

    $product->cover_path = null;

    expect($product->cover_path)->toBeNull();
});

it('sobe e desce as migrations de categoria e livro', function () {
    expect(Schema::hasTable('categories'))->toBeTrue()
        ->and(Schema::hasTable('products'))->toBeTrue();

    $this->artisan('migrate:rollback', [
        '--path' => 'database/migrations/2026_11_27_130001_create_products_table.php',
        '--force' => true,
    ])->assertSuccessful();

    $this->artisan('migrate:rollback', [
        '--path' => 'database/migrations/2026_11_27_130000_create_categories_table.php',
        '--force' => true,
    ])->assertSuccessful();

    expect(Schema::hasTable('products'))->toBeFalse()
        ->and(Schema::hasTable('categories'))->toBeFalse();

    $this->artisan('migrate', ['--force' => true])->assertSuccessful();

    expect(Schema::hasTable('categories'))->toBeTrue()
        ->and(Schema::hasTable('products'))->toBeTrue();
});
