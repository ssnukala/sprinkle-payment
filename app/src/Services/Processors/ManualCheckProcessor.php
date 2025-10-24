<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Services\Processors;

use UserFrosting\Sprinkle\Payment\Services\PaymentProcessorInterface;
use UserFrosting\Sprinkle\Payment\Database\Models\Payment;

/**
 * Manual Check Payment Processor
 *
 * Handles manual check payment processing
 */
class ManualCheckProcessor implements PaymentProcessorInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(Payment $payment, array $data): array
    {
        try {
            // Store check details
            $payment->paymentDetails()->create([
                'detail_type' => 'manual_check',
                'key' => 'check_number',
                'value' => $data['check_number'] ?? '',
                'data' => [
                    'check_number' => $data['check_number'] ?? '',
                    'check_date' => $data['check_date'] ?? now()->toDateString(),
                    'bank_name' => $data['bank_name'] ?? '',
                    'notes' => $data['notes'] ?? '',
                ],
            ]);

            // Manual check payments require manual verification
            return [
                'success' => true,
                'status' => 'pending',
                'transaction_id' => 'CHECK-' . ($data['check_number'] ?? uniqid()),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function refund(Payment $payment, float $amount): array
    {
        try {
            // Record refund details
            $payment->paymentDetails()->create([
                'detail_type' => 'manual_check_refund',
                'key' => 'refund_amount',
                'value' => (string)$amount,
                'data' => [
                    'amount' => $amount,
                    'refunded_at' => now()->toDateString(),
                ],
            ]);

            return [
                'success' => true,
                'refund_id' => 'CHECKREFUND-' . uniqid(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function verify(Payment $payment): array
    {
        try {
            // Manual verification
            return [
                'success' => true,
                'status' => $payment->status,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
