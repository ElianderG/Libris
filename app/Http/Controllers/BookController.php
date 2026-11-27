<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('category:id,name,slug')
            ->first();

        abort_if($product === null, 404);

        return Inertia::render('Book', [
            'book' => $product->toBookPage(),
        ]);
    }
}
