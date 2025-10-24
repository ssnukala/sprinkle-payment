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

use UserFrosting\Sprinkle\Payment\Database\Models\Order;
use Illuminate\Database\Eloquent\Collection;

/**
 * Order Repository
 *
 * Handles data access for orders
 */
class OrderRepository
{
    /**
     * Find order by ID
     */
    public function find(int $id): ?Order
    {
        return Order::find($id);
    }

    /**
     * Find order by order number
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::where('order_number', $orderNumber)->first();
    }

    /**
     * Get orders for a user
     */
    public function getByUser(int $userId): Collection
    {
        return Order::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get orders by status
     */
    public function getByStatus(string $status): Collection
    {
        return Order::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new order
     */
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    /**
     * Update an order
     */
    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }

    /**
     * Delete an order
     */
    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
