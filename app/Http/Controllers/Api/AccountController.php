<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateClick;
use App\Models\Commission;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Chave inválida.');
        }

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'affiliate_code' => $user->affiliate_code,
            'purchases' => [
                'count' => $this->paidPurchases($user->id)->count(),
                'total_cents' => (int) $this->paidPurchases($user->id)->sum('total_cents'),
            ],
            'sales' => [
                'count' => $this->confirmedCommissions($user->id)->count(),
                'total_cents' => (int) $this->confirmedCommissions($user->id)->sum('amount_cents'),
            ],
        ]);
    }

    public function purchases(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Chave inválida.');
        }

        $orders = $user->orders()
            ->with(['items', 'payments'])
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $order): array => $this->orderPayload($order))->values(),
            'meta' => $this->meta($orders),
        ]);
    }

    public function purchase(Request $request, int $order): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Chave inválida.');
        }

        $found = $user->orders()->whereKey($order)->first();

        abort_unless($found !== null && $user->can('view', $found), 404);

        $found->load(['items', 'payments']);

        return response()->json($this->orderPayload($found));
    }

    public function sales(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Chave inválida.');
        }

        $clicks = AffiliateClick::query()
            ->where('affiliate_user_id', $user->id)
            ->with('product:id,title')
            ->latest('id')
            ->paginate(20);

        $orders = $this->paidSales($user->id)->latest('id')->paginate(20);
        $commissionsByOrder = Commission::query()
            ->whereIn('order_id', $orders->pluck('id'))
            ->where('status', Commission::STATUS_CONFIRMED)
            ->get()
            ->keyBy('order_id');

        $commissions = $this->confirmedCommissions($user->id)->latest('id')->paginate(20);

        return response()->json([
            'clicks' => [
                'data' => $clicks->getCollection()->map(fn (AffiliateClick $click): array => [
                    'id' => $click->id,
                    'product_title' => $click->product?->title,
                    'created_at' => $click->created_at?->toIso8601String(),
                ])->values(),
                'meta' => $this->meta($clicks),
            ],
            'orders' => [
                'data' => $orders->getCollection()->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'total_cents' => $order->total_cents,
                    'commission_cents' => $commissionsByOrder->get($order->id)?->amount_cents,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])->values(),
                'meta' => $this->meta($orders),
            ],
            'commissions' => [
                'data' => $commissions->getCollection()->map(fn (Commission $commission): array => [
                    'id' => $commission->id,
                    'order_id' => $commission->order_id,
                    'base_cents' => $commission->base_cents,
                    'percent' => $commission->percent,
                    'amount_cents' => $commission->amount_cents,
                    'created_at' => $commission->created_at?->toIso8601String(),
                ])->values(),
                'meta' => $this->meta($commissions),
            ],
            'totals' => [
                'orders_cents' => (int) $this->paidSales($user->id)->sum('total_cents'),
                'commissions_cents' => (int) $this->confirmedCommissions($user->id)->sum('amount_cents'),
            ],
        ]);
    }

    /**
     * @return Builder<Order>
     */
    private function paidPurchases(int $userId): Builder
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('status', Order::STATUS_PAID);
    }

    /**
     * @return Builder<Order>
     */
    private function paidSales(int $userId): Builder
    {
        return Order::query()
            ->where('affiliate_id', $userId)
            ->where('user_id', '!=', $userId)
            ->where('status', Order::STATUS_PAID)
            ->whereHas('payments', fn ($query) => $query->where('status', Payment::STATUS_PAID));
    }

    /**
     * @return Builder<Commission>
     */
    private function confirmedCommissions(int $userId): Builder
    {
        return Commission::query()
            ->where('affiliate_user_id', $userId)
            ->where('status', Commission::STATUS_CONFIRMED);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'total_cents' => $order->total_cents,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'title' => $item->title,
                'unit_price_cents' => $item->unit_price_cents,
                'quantity' => $item->quantity,
            ])->values(),
            'payments' => $order->payments->sortByDesc('id')->values()->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount_cents' => $payment->amount_cents,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'created_at' => $payment->created_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
