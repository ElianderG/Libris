<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $category = $request->string('categoria')->toString();
        $search = $request->string('q')->toString();
        $order = $request->string('ordem')->toString() === 'preco' ? 'preco' : 'recente';

        $products = Product::publishedListing($category, $search, $order)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => $product->toCatalogCard());

        return Inertia::render('Catalog', [
            'products' => $products,
            'filters' => [
                'categoria' => $category,
                'q' => $search,
                'ordem' => $order,
            ],
            'categories' => Category::query()->orderBy('name')->get(['name', 'slug']),
        ]);
    }
}
