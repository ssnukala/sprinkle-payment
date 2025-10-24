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

use UserFrosting\Sprinkle\Payment\Database\Models\Payment;

/**
 * Payment Processor Interface
 *
 * Defines the contract for payment processors
 */
interface PaymentProcessorInterface
{
    /**
     * Process a payment
     *
     * @param Payment $payment The payment to process
     * @param array $data Additional payment data
     * @return array Result array with 'success' boolean and additional data
     */
    public function process(Payment $payment, array $data): array;

    /**
     * Refund a payment
     *
     * @param Payment $payment The payment to refund
     * @param float $amount The amount to refund
     * @return array Result array with 'success' boolean and additional data
     */
    public function refund(Payment $payment, float $amount): array;

    /**
     * Verify a payment status
     *
     * @param Payment $payment The payment to verify
     * @return array Result array with 'success' boolean and status information
     */
    public function verify(Payment $payment): array;
}
