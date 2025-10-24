<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Database\Migrations\v100;

use Illuminate\Database\Schema\Blueprint;
use UserFrosting\Sprinkle\Core\Database\Migration;

/**
 * Payments table migration
 * Stores payment records for orders (one order can have multiple payments)
 */
class PaymentsTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public static $dependencies = [
        OrdersTable::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('payments')) {
            $this->schema->create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('payment_number', 50)->unique();
                $table->string('payment_method', 2)->default('PP'); // e.g., PP = PayPal, ST = Stripe, AP- Apple Pay, GP - Google Pay, CC = Credit Card, BT = Bank Transfer
                $table->decimal('amount', 10, 2)->default(0.00);
                $table->string('currency', 3)->default('USD');
                $table->string('transaction_id', 255)->nullable();
                $table->string('authorization_code', 255)->nullable();
                $table->timestamp('authorized_at')->nullable();
                $table->timestamp('captured_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->string('status', 2)->default('PP'); // e.g., PP = Pending Payment, AU = Authorized, CA = Captured, CO = Completed, FA = Failed, RE = Refunded, CN = Cancelled
                $table->text('error_message')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('order_id');
                $table->index('payment_number');
                $table->index('payment_method');
                $table->index('status');
                $table->index('transaction_id');
                $table->index('created_at');

                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        $this->schema->dropIfExists('payments');
    }
}