<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['customer_id', 'user_id', 'status', 'delivery_date', 'reserved_at', 'delivered_at', 'paid_at', 'cancelled_at', 'price_per_lb', 'total', 'note'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'delivery_date' => 'date',
            'reserved_at' => 'datetime',
            'delivered_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'price_per_lb' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Sum of the payments registered for the order.
     */
    public function paidAmount(): float
    {
        if (array_key_exists('payments_sum_amount', $this->attributes)) {
            return round((float) $this->attributes['payments_sum_amount'], 2);
        }

        if ($this->relationLoaded('payments')) {
            return round((float) $this->payments->sum('amount'), 2);
        }

        return round((float) $this->payments()->sum('amount'), 2);
    }

    /**
     * Outstanding balance of the order.
     */
    public function balance(): float
    {
        return round((float) $this->total - $this->paidAmount(), 2);
    }

    /**
     * Limit the query to delivered orders with a pending balance.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeWithOutstandingBalance(Builder $query): void
    {
        $query->where('orders.status', OrderStatus::Entregado)
            ->whereRaw('orders.total > (select coalesce(sum(payments.amount), 0) from payments where payments.order_id = orders.id)');
    }

    public function isReserved(): bool
    {
        return $this->status === OrderStatus::Reservado;
    }

    public function isDelivered(): bool
    {
        return $this->status === OrderStatus::Entregado;
    }

    public function isCollected(): bool
    {
        return $this->status === OrderStatus::Cobrado;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Anulado;
    }

    /**
     * Determine whether the given user may edit the order.
     */
    public function canBeEditedBy(User $user): bool
    {
        if ($this->isReserved()) {
            return true;
        }

        return $this->isDelivered()
            && $user->isAdmin()
            && ! $this->payments()->exists();
    }

    /**
     * Determine whether the order may be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return ($this->isReserved() || $this->isDelivered())
            && ! $this->payments()->exists();
    }
}
