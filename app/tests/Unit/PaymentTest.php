<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UserFrosting\Sprinkle\Payment\Database\Models\Payment;

/**
 * Payment Model Test
 */
class PaymentTest extends TestCase
{
    public function testPaymentCreation(): void
    {
        $payment = new Payment([
            'order_id' => 1,
            'payment_number' => 'PAY-20250124-TEST01',
            'payment_method' => 'ST',
            'status' => 'PP',
            'amount' => 115.00,
            'currency' => 'USD',
        ]);

        $this->assertEquals('PAY-20250124-TEST01', $payment->payment_number);
        $this->assertEquals(115.00, $payment->amount);
        $this->assertEquals('ST', $payment->payment_method);
        $this->assertEquals('PP', $payment->status);
    }

    public function testIsSuccessful(): void
    {
        $payment = new Payment([
            'status' => 'CO',
        ]);

        $this->assertTrue($payment->isSuccessful());

        $payment->status = 'PP';
        $this->assertFalse($payment->isSuccessful());
    }

    public function testCanBeRefunded(): void
    {
        $payment = new Payment([
            'status' => 'CO',
            'refunded_at' => null,
        ]);

        $this->assertTrue($payment->canBeRefunded());

        $payment->status = 'PP';
        $this->assertFalse($payment->canBeRefunded());
    }
}
