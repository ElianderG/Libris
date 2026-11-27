<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __invoke(Request $request, string $slug): Response
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('category:id,name,slug')
            ->first();

        abort_if($product === null, 404);

        return Inertia::render('Book', [
            'book' => $product->toBookPage(),
            'referralUrl' => $this->referralUrl($request, $product),
        ]);
    }

    private function referralUrl(Request $request, Product $product): ?string
    {
        $code = $request->user()?->affiliate_code;

        if (! is_string($code) || $code === '') {
            return null;
        }

        $path = route('books.show', $product->slug, false);

        return $request->getSchemeAndHttpHost().$path.'?ref='.$code;
    }
}
