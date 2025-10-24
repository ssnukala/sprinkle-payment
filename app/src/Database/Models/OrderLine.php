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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * OrderLine Model
 *
 * Represents a line item in an order
 */
class OrderLine extends Model
{
    use SoftDeletes;

    /**
     * @var string The name of the table for this model
     */
    protected $table = 'order_lines';

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'order_id',
        'item_type',
        'item_id',
        'item_name',
        'item_description',
        'sku',
        'quantity',
        'unit_price',
        'subtotal',
        'tax',
        'discount',
        'total',
        'metadata',
    ];

    /**
     * @var array The attributes that should be cast
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the order that owns the order line.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Calculate line totals
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->quantity * $this->unit_price;
        $this->total = $this->subtotal + $this->tax - $this->discount;
    }
}
