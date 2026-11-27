<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Collection;
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
            'charts' => [
                'sales' => $this->salesChart(),
                'orders' => $this->ordersChart(),
                'books' => $this->booksChart(),
            ],
            'recent' => $recent,
        ]);
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function salesChart(): array
    {
        $timezone = (string) config('app.timezone');
        $start = now()->timezone($timezone)->startOfDay()->subDays(13);

        $totals = Order::query()
            ->where('status', Order::STATUS_PAID)
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'total_cents'])
            ->groupBy(fn (Order $order): string => $order->created_at?->timezone($timezone)->toDateString() ?? '')
            ->map(fn (Collection $orders): int => (int) $orders->sum('total_cents'));

        $labels = [];
        $values = [];

        for ($day = 0; $day < 14; $day++) {
            $date = $start->copy()->addDays($day);
            $labels[] = $date->format('d/m');
            $values[] = (int) ($totals[$date->toDateString()] ?? 0);
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function ordersChart(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => ['Pagos', 'Aguardando pagamento'],
            'values' => [
                (int) ($counts[Order::STATUS_PAID] ?? 0),
                (int) ($counts[Order::STATUS_PENDING_PAYMENT] ?? 0),
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function booksChart(): array
    {
        $categories = Category::query()
            ->withCount(['products as published' => fn ($query) => $query->where('is_published', true)])
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => (int) $category->getAttribute('published') > 0)
            ->values();

        $labels = $categories->map(fn (Category $category): string => $category->name)->all();
        $values = $categories->map(fn (Category $category): int => (int) $category->getAttribute('published'))->all();

        $loose = Product::query()->where('is_published', true)->whereNull('category_id')->count();

        if ($loose > 0) {
            $labels[] = 'Sem categoria';
            $values[] = $loose;
        }

        return [
            'labels' => array_values($labels),
            'values' => array_values($values),
        ];
    }
}
