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
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;

/**
 * PayPal Payment Processor
 *
 * Handles PayPal payment processing
 */
class PayPalProcessor implements PaymentProcessorInterface
{
    protected ApiContext $apiContext;

    public function __construct()
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                $_ENV['PAYPAL_CLIENT_ID'] ?? '',
                $_ENV['PAYPAL_CLIENT_SECRET'] ?? ''
            )
        );

        $this->apiContext->setConfig([
            'mode' => $_ENV['PAYPAL_MODE'] ?? 'sandbox',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function process(Payment $payment, array $data): array
    {
        try {
            if (isset($data['payment_id']) && isset($data['payer_id'])) {
                // Execute approved payment
                $paypalPayment = \PayPal\Api\Payment::get($data['payment_id'], $this->apiContext);
                
                $execution = new PaymentExecution();
                $execution->setPayerId($data['payer_id']);

                $result = $paypalPayment->execute($execution, $this->apiContext);

                $success = $result->getState() === 'approved';

                // Store payment details
                $payment->paymentDetails()->create([
                    'detail_type' => 'paypal_response',
                    'key' => 'payment_id',
                    'value' => $result->getId(),
                    'data' => [
                        'state' => $result->getState(),
                        'payer_id' => $data['payer_id'],
                    ],
                ]);

                return [
                    'success' => $success,
                    'status' => $success ? 'CO' : 'PP',
                    'transaction_id' => $result->getId(),
                ];
            } else {
                // Create new payment
                $payer = new Payer();
                $payer->setPaymentMethod('paypal');

                $amount = new Amount();
                $amount->setCurrency($payment->currency)
                       ->setTotal($payment->amount);

                $transaction = new Transaction();
                $transaction->setAmount($amount)
                           ->setDescription("Order #{$payment->order->order_number}");

                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl($data['return_url'] ?? '')
                            ->setCancelUrl($data['cancel_url'] ?? '');

                $paypalPayment = new \PayPal\Api\Payment();
                $paypalPayment->setIntent('sale')
                             ->setPayer($payer)
                             ->setRedirectUrls($redirectUrls)
                             ->setTransactions([$transaction]);

                $paypalPayment->create($this->apiContext);

                return [
                    'success' => true,
                    'status' => 'PP',
                    'approval_url' => $paypalPayment->getApprovalLink(),
                    'payment_id' => $paypalPayment->getId(),
                ];
            }
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
            // Get the sale transaction
            $paymentDetails = $payment->paymentDetails()
                ->where('detail_type', 'paypal_response')
                ->where('key', 'payment_id')
                ->first();

            if (!$paymentDetails) {
                return [
                    'success' => false,
                    'error' => 'Payment details not found',
                ];
            }

            $sale = Sale::get($paymentDetails->value, $this->apiContext);

            $refundRequest = new RefundRequest();
            $refundAmount = new Amount();
            $refundAmount->setCurrency($payment->currency)
                        ->setTotal($amount);
            $refundRequest->setAmount($refundAmount);

            $refund = $sale->refundSale($refundRequest, $this->apiContext);

            // Store refund details
            $payment->paymentDetails()->create([
                'detail_type' => 'paypal_refund',
                'key' => 'refund_id',
                'value' => $refund->getId(),
                'data' => [
                    'amount' => $amount,
                    'state' => $refund->getState(),
                ],
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->getId(),
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
            $paymentDetails = $payment->paymentDetails()
                ->where('detail_type', 'paypal_response')
                ->where('key', 'payment_id')
                ->first();

            if (!$paymentDetails) {
                return [
                    'success' => false,
                    'error' => 'Payment details not found',
                ];
            }

            $paypalPayment = \PayPal\Api\Payment::get($paymentDetails->value, $this->apiContext);

            return [
                'success' => true,
                'status' => $paypalPayment->getState(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
