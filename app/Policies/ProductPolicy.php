<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function read(User $user, Product $product): bool
    {
        return $user->orders()
            ->where('status', Order::STATUS_PAID)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Product $product): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->is_admin === true;
    }

    public function unpublish(User $user, Product $product): bool
    {
        return $user->is_admin === true;
    }
}
