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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Payment Model
 *
 * Represents a payment transaction for an order
 */
class Payment extends Model
{
    use SoftDeletes;

    /**
     * @var string The name of the table for this model
     */
    protected $table = 'payments';

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'order_id',
        'payment_number',
        'payment_method',
        'status',
        'amount',
        'currency',
        'transaction_id',
        'authorization_code',
        'authorized_at',
        'captured_at',
        'completed_at',
        'refunded_at',
        'error_message',
        'meta',
    ];

    /**
     * @var array The attributes that should be cast
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'authorized_at' => 'datetime',
        'captured_at' => 'datetime',
        'completed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment details for the payment.
     */
    public function paymentDetails(): HasMany
    {
        return $this->hasMany(PaymentDetail::class);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['CO', 'CA']);
    }

    /**
     * Check if payment can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->isSuccessful() && $this->refunded_at === null;
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = 'CO';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark payment as refunded
     */
    public function markAsRefunded(): void
    {
        $this->status = 'RE';
        $this->refunded_at = now();
        $this->save();
    }
}
