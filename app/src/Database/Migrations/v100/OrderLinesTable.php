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
 * Order Lines table migration
 * Stores individual line items for each order
 */
class OrderLinesTable extends Migration
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
        if (!$this->schema->hasTable('order_lines')) {
            $this->schema->create('order_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('item_type', 100); // product, service, subscription, etc.
                $table->unsignedBigInteger('item_id')->nullable(); // Reference to product/service table
                $table->string('item_name', 255);
                $table->text('item_description')->nullable();
                $table->string('sku', 100)->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 10, 2)->default(0.00);
                $table->decimal('subtotal', 10, 2)->default(0.00);
                $table->decimal('tax', 10, 2)->default(0.00);
                $table->decimal('discount', 10, 2)->default(0.00);
                $table->decimal('total', 10, 2)->default(0.00);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('order_id');
                $table->index('item_type');
                $table->index('item_id');

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
        $this->schema->dropIfExists('order_lines');
    }
}