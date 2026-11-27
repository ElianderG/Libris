<?php

namespace App\Services\Orders;

use App\Models\AffiliateClick;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlaceOrder
{
    public function __construct(private Cart $cart) {}

    public function place(User $user, string $gateway = Order::GATEWAY_JUNTOS): Order
    {
        $products = $this->cart->items();

        if ($products->isEmpty()) {
            throw new RuntimeException('A sacola está vazia.');
        }

        if (! in_array($gateway, [Order::GATEWAY_JUNTOS, Order::GATEWAY_PAGSMILE], true)) {
            $gateway = Order::GATEWAY_JUNTOS;
        }

        $order = DB::transaction(function () use ($user, $products, $gateway): Order {
            $productIds = array_values(
                $products->map(fn (Product $product): int => $product->id)->sort()->all(),
            );

            if ($this->hasRecentDuplicate($user, $productIds)) {
                throw new RuntimeException('Esse pedido acabou de ser registrado. Espere um instante para tentar de novo.');
            }

            $total = 0;

            foreach ($products as $product) {
                $total += $product->price_cents;
            }

            [$affiliateId, $affiliateCode] = $this->affiliateFor($user);

            $order = new Order;
            $order->user_id = $user->id;
            $order->status = Order::STATUS_PENDING_PAYMENT;
            $order->gateway = $gateway;
            $order->total_cents = $total;
            $order->affiliate_id = $affiliateId;
            $order->affiliate_code = $affiliateCode;
            $order->save();

            foreach ($products as $product) {
                $item = new OrderItem;
                $item->order_id = $order->id;
                $item->product_id = $product->id;
                $item->title = $product->title;
                $item->unit_price_cents = $product->price_cents;
                $item->quantity = 1;
                $item->save();
            }

            $stored = (int) $order->items()->get()->sum(
                fn (OrderItem $item): int => $item->unit_price_cents * $item->quantity,
            );

            if ($order->items()->count() === 0 || $stored !== $order->total_cents) {
                throw new RuntimeException('O total do pedido não confere.');
            }

            return $order;
        });

        $this->cart->clear();

        return $order;
    }

    /**
     * @param  list<int>  $productIds
     */
    private function hasRecentDuplicate(User $user, array $productIds): bool
    {
        $recentOrders = Order::query()
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_PENDING_PAYMENT)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->with('items:id,order_id,product_id')
            ->lockForUpdate()
            ->get();

        foreach ($recentOrders as $existing) {
            $existingIds = array_values(
                $existing->items
                    ->map(fn (OrderItem $item): int => $item->product_id)
                    ->sort()
                    ->all(),
            );

            if ($existingIds === $productIds) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function affiliateFor(User $buyer): array
    {
        $token = request()->cookie('libris_ref');

        if (! is_string($token) || $token === '') {
            return [null, null];
        }

        $click = AffiliateClick::query()->where('token', $token)->first();
        $days = max(1, (int) config('libris.affiliate.cookie_days'));

        if ($click === null || $click->created_at === null || $click->created_at->lt(now()->subDays($days))) {
            return [null, null];
        }

        if ($click->affiliate_user_id === $buyer->id) {
            return [null, null];
        }

        $affiliate = $click->affiliate;

        if ($affiliate->affiliate_code === '') {
            return [null, null];
        }

        return [$affiliate->id, $affiliate->affiliate_code];
    }
}
