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
use UserFrosting\Sprinkle\Payment\Database\Repositories\OrderRepository;
use UserFrosting\Sprinkle\Payment\Database\Repositories\PaymentRepository;
use UserFrosting\Sprinkle\Payment\Database\Models\Order;
use UserFrosting\Sprinkle\Payment\Database\Models\Payment;

/**
 * Payment Service
 *
 * Handles payment processing and orchestration
 */
class PaymentService
{
    #[Inject]
    protected OrderRepository $orderRepository;

    #[Inject]
    protected PaymentRepository $paymentRepository;

    /**
     * Create a new order with line items
     */
    public function createOrder(int $userId, array $lineItems, array $orderData = []): Order
    {
        $orderNumber = $this->orderRepository->generateOrderNumber();

        $order = $this->orderRepository->create([
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
        foreach ($lineItems as $item) {
            $order->orderLines()->create($item);
        }

        // Calculate totals
        $order->calculateTotal();
        $order->save();

        return $order;
    }

    /**
     * Process a payment for an order
     */
    public function processPayment(Order $order, string $paymentMethod, float $amount, array $paymentData = []): Payment
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
            if ($order->isPaid()) {
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
     * Refund a payment
     */
    public function refundPayment(Payment $payment, float $amount = null): bool
    {
        if (!$payment->canBeRefunded()) {
            return false;
        }

        $refundAmount = $amount ?? $payment->amount;
        $processor = $this->getPaymentProcessor($payment->payment_method);
        
        $result = $processor->refund($payment, $refundAmount);

        if ($result['success']) {
            $payment->markAsRefunded();
            
            // Update order status
            if ($payment->order) {
                $payment->order->update(['status' => 'RE']);
            }

            return true;
        }

        return false;
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
