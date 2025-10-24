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
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;

/**
 * Stripe Payment Processor
 *
 * Handles Stripe payment processing
 */
class StripeProcessor implements PaymentProcessorInterface
{
    protected string $apiKey;

    public function __construct()
    {
        // Load from config
        $this->apiKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        Stripe::setApiKey($this->apiKey);
    }

    /**
     * {@inheritDoc}
     */
    public function process(Payment $payment, array $data): array
    {
        try {
            // Create or confirm payment intent
            if (isset($data['payment_intent_id'])) {
                $intent = PaymentIntent::retrieve($data['payment_intent_id']);
                
                if ($intent->status === 'requires_confirmation') {
                    $intent = $intent->confirm();
                }
            } else {
                $intent = PaymentIntent::create([
                    'amount' => (int)($payment->amount * 100), // Convert to cents
                    'currency' => strtolower($payment->currency),
                    'description' => "Order #{$payment->order->order_number}",
                    'metadata' => [
                        'order_id' => $payment->order_id,
                        'payment_id' => $payment->id,
                    ],
                ]);
            }

            // Store payment details
            $payment->paymentDetails()->create([
                'detail_type' => 'stripe_response',
                'key' => 'payment_intent_id',
                'value' => $intent->id,
                'data' => [
                    'status' => $intent->status,
                    'client_secret' => $intent->client_secret,
                ],
            ]);

            $success = in_array($intent->status, ['succeeded', 'processing']);

            return [
                'success' => $success,
                'status' => $success ? 'CO' : 'PP',
                'transaction_id' => $intent->id,
                'intent' => $intent,
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
            $refund = Refund::create([
                'payment_intent' => $payment->transaction_id,
                'amount' => (int)($amount * 100), // Convert to cents
            ]);

            // Store refund details
            $payment->paymentDetails()->create([
                'detail_type' => 'stripe_refund',
                'key' => 'refund_id',
                'value' => $refund->id,
                'data' => [
                    'amount' => $amount,
                    'status' => $refund->status,
                ],
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
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
            $intent = PaymentIntent::retrieve($payment->transaction_id);

            return [
                'success' => true,
                'status' => $intent->status,
                'amount' => $intent->amount / 100,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
