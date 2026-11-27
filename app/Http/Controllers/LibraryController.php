<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $productIds = OrderItem::query()
            ->whereHas('order', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('status', Order::STATUS_PAID);
            })
            ->select('product_id')
            ->distinct()
            ->pluck('product_id');

        $books = Product::query()
            ->whereIn('id', $productIds)
            ->orderBy('title')
            ->get()
            ->map(fn (Product $product): array => [
                'title' => $product->title,
                'author' => $product->author,
                'slug' => $product->slug,
                'read_url' => route('library.show', $product->slug),
            ])
            ->values();

        return Inertia::render('Library', [
            'books' => $books,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $product = Product::query()->where('slug', $slug)->first();

        if ($product === null) {
            abort(404);
        }

        $owns = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where('status', Order::STATUS_PAID);
            })
            ->exists();

        if (! $owns) {
            abort(403);
        }

        return Inertia::render('LibraryBook', [
            'book' => [
                'title' => $product->title,
                'author' => $product->author,
                'content' => $product->content,
            ],
        ]);
    }
}
