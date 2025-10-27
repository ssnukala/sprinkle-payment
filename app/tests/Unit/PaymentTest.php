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

/**
 * Payment Schema Test
 * 
 * Tests that payment schema is valid and properly configured
 */
class PaymentTest extends TestCase
{
    public function testPaymentSchemaExists(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/payment.json';
        $this->assertFileExists($schemaPath);
        
        $schema = json_decode(file_get_contents($schemaPath), true);
        $this->assertNotNull($schema);
        $this->assertEquals('payment', $schema['model']);
        $this->assertEquals('payments', $schema['table']);
    }

    public function testPaymentSchemaHasRequiredFields(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/payment.json';
        $schema = json_decode(file_get_contents($schemaPath), true);
        
        $this->assertArrayHasKey('fields', $schema);
        $this->assertArrayHasKey('order_id', $schema['fields']);
        $this->assertArrayHasKey('payment_number', $schema['fields']);
        $this->assertArrayHasKey('payment_method', $schema['fields']);
        $this->assertArrayHasKey('status', $schema['fields']);
        $this->assertArrayHasKey('amount', $schema['fields']);
    }

    public function testPaymentSchemaHasDetailSection(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/payment.json';
        $schema = json_decode(file_get_contents($schemaPath), true);
        
        $this->assertArrayHasKey('detail', $schema);
        $this->assertEquals('payment_detail', $schema['detail']['model']);
        $this->assertEquals('payment_id', $schema['detail']['foreign_key']);
    }

    public function testPaymentDetailSchemaExists(): void
    {
        $schemaPath = __DIR__ . '/../../schema/crud6/payment_detail.json';
        $this->assertFileExists($schemaPath);
        
        $schema = json_decode(file_get_contents($schemaPath), true);
        $this->assertNotNull($schema);
        $this->assertEquals('payment_detail', $schema['model']);
        $this->assertEquals('payment_details', $schema['table']);
    }
}
