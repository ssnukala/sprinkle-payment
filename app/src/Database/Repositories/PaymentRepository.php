<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Database\Repositories;

use UserFrosting\Sprinkle\Payment\Database\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Payment Repository
 *
 * Handles data access for payments
 */
class PaymentRepository
{
    /**
     * Find payment by ID
     */
    public function find(int $id): ?Payment
    {
        return Payment::find($id);
    }

    /**
     * Find payment by payment number
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return Payment::where('payment_number', $paymentNumber)->first();
    }

    /**
     * Find payment by transaction ID
     */
    public function findByTransactionId(string $transactionId): ?Payment
    {
        return Payment::where('transaction_id', $transactionId)->first();
    }

    /**
     * Get payments for an order
     */
    public function getByOrder(int $orderId): Collection
    {
        return Payment::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status): Collection
    {
        return Payment::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by method
     */
    public function getByMethod(string $method): Collection
    {
        return Payment::where('payment_method', $method)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new payment
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Update a payment
     */
    public function update(Payment $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * Delete a payment
     */
    public function delete(Payment $payment): bool
    {
        return $payment->delete();
    }

    /**
     * Generate unique payment number
     */
    public function generatePaymentNumber(): string
    {
        do {
            $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (Payment::where('payment_number', $paymentNumber)->exists());

        return $paymentNumber;
    }
}
