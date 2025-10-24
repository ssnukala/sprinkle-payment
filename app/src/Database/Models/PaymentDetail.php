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

/**
 * PaymentDetail Model
 *
 * Represents detailed information about a payment transaction
 */
class PaymentDetail extends Model
{
    /**
     * @var string The name of the table for this model
     */
    protected $table = 'payment_details';

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'payment_id',
        'detail_type',
        'key',
        'value',
        'data',
    ];

    /**
     * @var array The attributes that should be cast
     */
    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the payment that owns the payment detail.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
