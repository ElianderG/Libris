<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('semeia o catálogo e não duplica na segunda execução', function () {
    $this->seed();

    $ids = Product::query()->orderBy('id')->pluck('id');

    $this->seed();

    $published = Product::query()->where('is_published', true);
    $hidden = Product::query()->where('slug', 'o-relogio-da-padaria')->first();

    expect(Category::query()->count())->toBe(3)
        ->and(Product::query()->count())->toBe(9)
        ->and($published->count())->toBe(8)
        ->and(Product::query()->where('is_featured', true)->count())->toBe(4)
        ->and(Product::query()->where('is_published', false)->count())->toBe(1)
        ->and(Product::query()->where('price_cents', 3790)->exists())->toBeTrue()
        ->and(Product::query()->whereNotNull('cover_path')->count())->toBe(2)
        ->and(Product::query()->whereNull('cover_path')->count())->toBe(7)
        ->and(Product::query()->pluck('slug')->unique())->toHaveCount(9)
        ->and(Product::query()->orderBy('id')->pluck('id'))->toEqual($ids);

    expect($hidden)->not->toBeNull()
        ->and($hidden->is_published)->toBeFalse()
        ->and($hidden->is_featured)->toBeFalse();

    $covers = Product::query()->whereNotNull('cover_path')->get();

    expect($covers)->toHaveCount(2);

    foreach ($covers as $product) {
        $file = public_path((string) $product->cover_path);

        expect($product->toCatalogCard()['cover_url'])->toBe('/'.$product->cover_path)
            ->and(is_file($file))->toBeTrue()
            ->and(file_get_contents($file))
            ->toContain('<svg')
            ->toContain($product->title);
    }
});
