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

use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use DI\Attribute\Inject;

/**
 * Payment Repository
 *
 * Handles data access for payments using CRUD6 SchemaService
 */
class PaymentRepository
{
    #[Inject]
    protected SchemaService $schemaService;

    /**
     * Get the payment model instance
     */
    protected function getModel(): Model
    {
        return $this->schemaService->getModelInstance('payment');
    }

    /**
     * Find payment by ID
     */
    public function find(int $id): ?Model
    {
        return $this->getModel()->find($id);
    }

    /**
     * Find payment by payment number
     */
    public function findByPaymentNumber(string $paymentNumber): ?Model
    {
        return $this->getModel()->where('payment_number', $paymentNumber)->first();
    }

    /**
     * Find payment by transaction ID
     */
    public function findByTransactionId(string $transactionId): ?Model
    {
        return $this->getModel()->where('transaction_id', $transactionId)->first();
    }

    /**
     * Get payments for an order
     */
    public function getByOrder(int $orderId): Collection
    {
        return $this->getModel()->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->getModel()->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by method
     */
    public function getByMethod(string $method): Collection
    {
        return $this->getModel()->where('payment_method', $method)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new payment
     */
    public function create(array $data): Model
    {
        return $this->getModel()->create($data);
    }

    /**
     * Update a payment
     */
    public function update(Model $payment, array $data): bool
    {
        return $payment->update($data);
    }

    /**
     * Delete a payment
     */
    public function delete(Model $payment): bool
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
        } while ($this->getModel()->where('payment_number', $paymentNumber)->exists());

        return $paymentNumber;
    }
}
