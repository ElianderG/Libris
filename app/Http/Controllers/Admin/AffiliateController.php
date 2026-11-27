<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    public function index(): Response
    {
        $affiliates = User::query()
            ->select(['id', 'name', 'affiliate_code'])
            ->where(function ($query): void {
                $query->whereHas('affiliateClicks')->orWhereHas('commissions');
            })
            ->withCount([
                'affiliateClicks as clicks_count',
                'referredOrders as paid_orders_count' => fn ($query) => $query->where('status', Order::STATUS_PAID),
            ])
            ->withSum(
                ['commissions as confirmed_cents' => fn ($query) => $query->where('status', Commission::STATUS_CONFIRMED)],
                'amount_cents',
            )
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'code' => $user->affiliate_code,
                'clicks' => (int) $user->getAttribute('clicks_count'),
                'paid_orders' => (int) $user->getAttribute('paid_orders_count'),
                'commission' => Money::format((int) $user->getAttribute('confirmed_cents')),
            ])
            ->values();

        return Inertia::render('admin/affiliates/Index', [
            'affiliates' => $affiliates,
        ]);
    }
}
