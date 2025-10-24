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
 * Orders table migration
 * Stores order information for payments
 */
class OrdersTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public static $dependencies = [];

    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('orders')) {
            $this->schema->create('orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('order_number', 50)->unique();
                $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'refunded'])->default('pending');
                $table->decimal('subtotal', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('shipping', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('total', 10, 2)->default(0.00);
                $table->string('currency', 3)->default('USD');
                $table->text('customer_notes')->nullable();
                $table->text('admin_notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('user_id');
                $table->index('order_number');
                $table->index('status');
                $table->index('created_at');

                $table->foreign('user_id')
                      ->references('id')
                      ->on('users')
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
        $this->schema->dropIfExists('orders');
    }
}
