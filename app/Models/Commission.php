<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $affiliate_user_id
 * @property int $order_id
 * @property int $payment_id
 * @property int $base_cents
 * @property int $percent
 * @property int $amount_cents
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $affiliate
 * @property-read Order $order
 * @property-read Payment $payment
 */
class Commission extends Model
{
    public const STATUS_CONFIRMED = 'confirmed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_cents' => 'integer',
            'percent' => 'integer',
            'amount_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affiliate_user_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
