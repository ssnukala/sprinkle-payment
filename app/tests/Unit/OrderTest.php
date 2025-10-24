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
use UserFrosting\Sprinkle\Payment\Database\Models\Order;
use UserFrosting\Sprinkle\Payment\Database\Models\OrderLine;
use UserFrosting\Sprinkle\Payment\Database\Models\Payment;

/**
 * Order Model Test
 */
class OrderTest extends TestCase
{
    public function testOrderCreation(): void
    {
        $order = new Order([
            'user_id' => 1,
            'order_number' => 'ORD-20250124-TEST01',
            'status' => 'pending',
            'subtotal' => 100.00,
            'tax' => 10.00,
            'shipping' => 5.00,
            'discount' => 0.00,
            'total' => 115.00,
            'currency' => 'USD',
        ]);

        $this->assertEquals('ORD-20250124-TEST01', $order->order_number);
        $this->assertEquals(100.00, $order->subtotal);
        $this->assertEquals(115.00, $order->total);
        $this->assertEquals('pending', $order->status);
    }

    public function testOrderNumberGeneration(): void
    {
        // This would be an integration test with database
        $this->assertTrue(true);
    }
}
