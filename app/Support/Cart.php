<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class Cart
{
    private const SESSION_KEY = 'cart';

    public function add(string $slug): bool
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if ($product === null) {
            return false;
        }

        $ids = $this->ids();

        if (! in_array($product->id, $ids, true)) {
            $ids[] = $product->id;
            session([self::SESSION_KEY => $ids]);
        }

        return true;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function remove(string $slug): void
    {
        $productId = Product::query()->where('slug', $slug)->value('id');

        if ($productId === null) {
            return;
        }

        $productId = (int) $productId;

        session([
            self::SESSION_KEY => array_values(array_filter(
                $this->ids(),
                fn (int $id): bool => $id !== $productId,
            )),
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    public function items(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return new Collection;
        }

        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('is_published', true)
            ->with('category:id,name,slug')
            ->get()
            ->keyBy('id');

        $kept = [];
        $ordered = new Collection;

        foreach ($ids as $id) {
            $product = $products->get($id);

            if ($product instanceof Product) {
                $kept[] = $id;
                $ordered->push($product);
            }
        }

        if ($kept !== $ids) {
            session([self::SESSION_KEY => $kept]);
        }

        return $ordered;
    }

    /**
     * @return list<int>
     */
    private function ids(): array
    {
        $stored = session(self::SESSION_KEY, []);

        if (! is_array($stored)) {
            return [];
        }

        $ids = [];

        foreach ($stored as $id) {
            if (is_int($id)) {
                $ids[] = $id;
            } elseif (is_string($id) && ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }
}
