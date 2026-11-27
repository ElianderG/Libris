<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $commissions = Commission::query()
            ->where('affiliate_user_id', $user->id)
            ->latest('id')
            ->get()
            ->map(fn (Commission $commission): array => [
                'created_at' => $commission->created_at?->timezone((string) config('app.timezone'))->format('d/m/Y H:i'),
                'order_id' => $commission->order_id,
                'base' => Money::format($commission->base_cents),
                'amount' => Money::format($commission->amount_cents),
            ])
            ->values();

        return Inertia::render('Affiliate', [
            'commissions' => $commissions,
        ]);
    }
}
