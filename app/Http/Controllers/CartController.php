<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function show(Cart $cart): Response
    {
        $items = $cart->items();

        return Inertia::render('Cart', [
            'items' => $items
                ->map(fn (Product $product): array => $product->toCatalogCard())
                ->values(),
            'total' => Money::format((int) $items->sum('price_cents')),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $slug = $request->string('slug')->toString();

        if (! $cart->add($slug)) {
            return back()->with('error', 'Esse livro não está disponível.');
        }

        if ($request->boolean('checkout')) {
            return redirect()->route('checkout');
        }

        return back()->with('success', 'Livro adicionado à sacola.');
    }

    public function destroy(string $slug, Cart $cart): RedirectResponse
    {
        $cart->remove($slug);

        return back(fallback: route('cart'))->with('success', 'Livro removido da sacola.');
    }

    public function clear(Cart $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->route('cart')->with('success', 'Sacola esvaziada.');
    }
}
