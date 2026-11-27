<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Support\Money;
use App\Support\ProductRules;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $author
 * @property string $description
 * @property string|null $cover_path
 * @property int $price_cents
 * @property string $content
 * @property bool $is_featured
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 * @property-read Collection<int, OrderItem> $orderItems
 */
#[Fillable(['category_id', 'title', 'author', 'description', 'content', 'cover_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUniqueSlug;

    protected $attributes = [
        'is_featured' => false,
        'is_published' => false,
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return Builder<static>
     */
    public static function publishedListing(?string $categorySlug, ?string $search, string $order): Builder
    {
        $categorySlug = trim((string) $categorySlug);
        $search = mb_substr(trim((string) $search), 0, 80);

        $query = static::query()
            ->where('is_published', true)
            ->with('category:id,name,slug');

        if ($categorySlug !== '') {
            $query->whereHas('category', function (Builder $category) use ($categorySlug): void {
                $category->where('slug', $categorySlug);
            });
        }

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function (Builder $books) use ($like): void {
                $books->whereRaw('title like ? escape ?', [$like, '\\'])
                    ->orWhereRaw('author like ? escape ?', [$like, '\\']);
            });
        }

        if ($order === 'preco') {
            return $query->orderBy('price_cents')->orderByDesc('id');
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * @return array{slug: string, title: string, author: string, category: string, price: string, cover_url: string|null}
     */
    public function toCatalogCard(): array
    {
        $cover = is_string($this->cover_path) ? ProductRules::safeCoverPath($this->cover_path) : null;

        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'author' => $this->author,
            'category' => $this->category->name,
            'price' => Money::format($this->price_cents),
            'cover_url' => $cover !== null ? '/'.$cover : null,
        ];
    }

    /**
     * @return array{slug: string, title: string, author: string, category: string, description: string, price: string, cover_url: string|null, preview: string}
     */
    public function toBookPage(): array
    {
        return [
            ...$this->toCatalogCard(),
            'description' => $this->description,
            'preview' => $this->readingPreview(),
        ];
    }

    public function readingPreview(int $paragraphs = 2): string
    {
        $parts = preg_split("/\R\s*\R/u", trim($this->content));

        if ($parts === false) {
            return '';
        }

        $parts = array_values(array_filter($parts, fn (string $part): bool => trim($part) !== ''));

        return implode("\n\n", array_slice($parts, 0, max(1, $paragraphs)));
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    protected static function slugFallback(): string
    {
        return 'livro';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return Attribute<int, int>
     */
    protected function priceCents(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): int => (int) $value,
            set: fn (mixed $value): int => ProductRules::parseCents($value),
        );
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function coverPath(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null,
            set: fn (mixed $value): ?string => ProductRules::normalizeCoverPath($value),
        );
    }
}
