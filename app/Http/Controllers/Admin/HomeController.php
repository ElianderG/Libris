<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $paid = Order::query()->where('status', Order::STATUS_PAID);

        $recent = Order::query()
            ->with('user:id,name')
            ->where('status', Order::STATUS_PAID)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'created_at' => $order->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                'buyer' => $order->user->name,
                'total' => Money::format($order->total_cents),
                'detail_url' => route('admin.orders.show', $order),
            ])
            ->values();

        return Inertia::render('admin/Home', [
            'stats' => [
                'published_books' => Product::query()->where('is_published', true)->count(),
                'paid_orders' => (clone $paid)->count(),
                'sold_total' => Money::format((int) (clone $paid)->sum('total_cents')),
                'confirmed_commissions' => Commission::query()
                    ->where('status', Commission::STATUS_CONFIRMED)
                    ->count(),
            ],
            'recent' => $recent,
        ]);
    }
}
