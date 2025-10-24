<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UserFrosting\Sprinkle\Account\Database\Models\User;

/**
 * Order Model
 *
 * Represents an order in the system
 */
class Order extends Model
{
    use SoftDeletes;

    /**
     * @var string The name of the table for this model
     */
    protected $table = 'orders';

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'subtotal',
        'tax',
        'shipping',
        'discount',
        'total',
        'currency',
        'customer_notes',
        'admin_notes',
        'meta',
    ];

    /**
     * @var array The attributes that should be cast
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order lines for the order.
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * Get the payments for the order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate total from order lines
     */
    public function calculateTotal(): void
    {
        $this->subtotal = $this->orderLines->sum('subtotal');
        $this->tax = $this->orderLines->sum('tax');
        $this->total = $this->subtotal + $this->tax + $this->shipping - $this->discount;
    }

    /**
     * Check if order is paid in full
     */
    public function isPaid(): bool
    {
        $totalPaid = $this->payments()
            ->whereIn('status', ['CO', 'CA'])
            ->sum('amount');

        return $totalPaid >= $this->total;
    }

    /**
     * Get remaining balance
     */
    public function getRemainingBalance(): float
    {
        $totalPaid = $this->payments()
            ->whereIn('status', ['CO', 'CA'])
            ->sum('amount');

        return max(0, $this->total - $totalPaid);
    }
}
