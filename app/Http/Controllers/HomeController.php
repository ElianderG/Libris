<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $featured = Product::publishedListing(null, null, 'recente')
            ->where('is_featured', true)
            ->limit(8)
            ->get()
            ->map(fn (Product $product): array => $product->toCatalogCard())
            ->all();

        $recent = Product::publishedListing(null, null, 'recente')
            ->where('is_featured', false)
            ->limit(4)
            ->get()
            ->map(fn (Product $product): array => $product->toCatalogCard())
            ->all();

        return Inertia::render('Welcome', [
            'featured' => $featured,
            'recent' => $recent,
        ]);
    }
}
