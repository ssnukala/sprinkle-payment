<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Services;

use DI\Attribute\Inject;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\Payment\Database\Repositories\PaymentRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment Service
 *
 * Handles payment processing and orchestration
 */
class PaymentService
{
    #[Inject]
    protected PaymentRepository $paymentRepository;

    #[Inject]
    protected SchemaService $schemaService;

    /**
     * Create a new order with line items
     * 
     * Note: This uses sales_order from sprinkle-orders via CRUD6
     */
    public function createOrder(int $userId, array $lineItems, array $orderData = []): Model
    {
        $orderModel = $this->schemaService->getModelInstance('sales_order');
        $orderNumber = $this->generateOrderNumber($orderModel);

        $order = $orderModel->create([
            'user_id' => $userId,
            'order_number' => $orderNumber,
            'status' => 'PP',
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => $orderData['shipping'] ?? 0,
            'discount' => $orderData['discount'] ?? 0,
            'total' => 0,
            'currency' => $orderData['currency'] ?? 'USD',
            'customer_notes' => $orderData['customer_notes'] ?? null,
            'admin_notes' => $orderData['admin_notes'] ?? null,
            'meta' => $orderData['meta'] ?? null,
        ]);

        // Add line items
        $orderLineModel = $this->schemaService->getModelInstance('sales_order_lines');
        foreach ($lineItems as $item) {
            $item['order_id'] = $order->id;
            $orderLineModel->create($item);
        }

        // Calculate totals
        $this->calculateOrderTotal($order);
        $order->save();

        return $order;
    }

    /**
     * Calculate order totals from line items
     */
    protected function calculateOrderTotal(Model $order): void
    {
        $orderLineModel = $this->schemaService->getModelInstance('sales_order_lines');
        $orderLines = $orderLineModel->where('order_id', $order->id)->get();
        
        $order->subtotal = $orderLines->sum('subtotal');
        $order->tax = $orderLines->sum('tax');
        $order->total = $order->subtotal + $order->tax + $order->shipping - $order->discount;
    }

    /**
     * Generate unique order number
     */
    protected function generateOrderNumber(Model $orderModel): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while ($orderModel->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Process a payment for an order
     */
    public function processPayment(Model $order, string $paymentMethod, float $amount, array $paymentData = []): Model
    {
        $paymentNumber = $this->paymentRepository->generatePaymentNumber();
        
        // Normalize payment method to 2-character code
        $paymentMethodCode = $this->normalizePaymentMethod($paymentMethod);

        $payment = $this->paymentRepository->create([
            'order_id' => $order->id,
            'payment_number' => $paymentNumber,
            'payment_method' => $paymentMethodCode,
            'status' => 'PP',
            'amount' => $amount,
            'currency' => $order->currency,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'authorization_code' => $paymentData['authorization_code'] ?? null,
            'meta' => $paymentData['meta'] ?? null,
        ]);

        // Delegate to specific payment processor
        $processor = $this->getPaymentProcessor($paymentMethodCode);
        $result = $processor->process($payment, $paymentData);

        // Update payment status based on result
        if ($result['success']) {
            $payment->update([
                'status' => $result['status'] ?? 'CO',
                'transaction_id' => $result['transaction_id'] ?? $payment->transaction_id,
                'completed_at' => now(),
            ]);

            // Update order status if fully paid
            if ($this->isOrderPaid($order)) {
                $order->update(['status' => 'CO']);
            }
        } else {
            $payment->update([
                'status' => 'FA',
                'error_message' => $result['error'] ?? 'Payment processing failed',
            ]);
        }

        return $payment;
    }

    /**
     * Check if order is paid in full
     */
    protected function isOrderPaid(Model $order): bool
    {
        $paymentModel = $this->schemaService->getModelInstance('payment');
        $totalPaid = $paymentModel->where('order_id', $order->id)
            ->whereIn('status', ['CO', 'CA'])
            ->sum('amount');

        return $totalPaid >= $order->total;
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Model $payment, float $amount = null): bool
    {
        if (!$this->canBeRefunded($payment)) {
            return false;
        }

        $refundAmount = $amount ?? $payment->amount;
        $processor = $this->getPaymentProcessor($payment->payment_method);
        
        $result = $processor->refund($payment, $refundAmount);

        if ($result['success']) {
            $payment->update([
                'status' => 'RE',
                'refunded_at' => now(),
            ]);
            
            // Update order status
            $orderModel = $this->schemaService->getModelInstance('sales_order');
            $order = $orderModel->find($payment->order_id);
            if ($order) {
                $order->update(['status' => 'RE']);
            }

            return true;
        }

        return false;
    }

    /**
     * Check if payment can be refunded
     */
    protected function canBeRefunded(Model $payment): bool
    {
        $isSuccessful = in_array($payment->status, ['CO', 'CA']);
        return $isSuccessful && $payment->refunded_at === null;
    }

    /**
     * Normalize payment method to 2-character code
     * Accepts both full names (e.g., 'stripe', 'paypal') and codes (e.g., 'ST', 'PP')
     */
    protected function normalizePaymentMethod(string $method): string
    {
        // If already a 2-character code, return as-is
        if (strlen($method) === 2 && ctype_upper($method)) {
            return $method;
        }

        // Map full names to codes
        $methodMap = [
            'stripe' => 'ST',
            'paypal' => 'PP',
            'apple_pay' => 'AP',
            'google_pay' => 'GP',
            'manual_check' => 'MC',
        ];

        return $methodMap[strtolower($method)] ?? 'MC'; // Default to manual check
    }

    /**
     * Get payment processor for a specific method
     */
    protected function getPaymentProcessor(string $method): PaymentProcessorInterface
    {
        return match ($method) {
            'PP' => new Processors\PayPalProcessor(),
            'ST' => new Processors\StripeProcessor(),
            'AP' => new Processors\ApplePayProcessor(),
            'GP' => new Processors\GooglePayProcessor(),
            'MC' => new Processors\ManualCheckProcessor(),
            default => new Processors\ManualCheckProcessor(),
        };
    }
}
