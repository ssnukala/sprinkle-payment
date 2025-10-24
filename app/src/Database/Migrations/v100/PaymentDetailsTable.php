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
 * Payment Details table migration
 * Stores detailed information about each payment transaction
 */
class PaymentDetailsTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public static $dependencies = [
        PaymentsTable::class,
    ];

    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        if (!$this->schema->hasTable('payment_details')) {
            $this->schema->create('payment_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->string('detail_type', 100); // gateway_response, refund_detail, chargeback_detail, etc.
                $table->string('key', 255);
                $table->text('value')->nullable();
                $table->json('data')->nullable();
                $table->timestamps();

                $table->index('payment_id');
                $table->index('detail_type');
                $table->index('key');

                $table->foreign('payment_id')
                      ->references('id')
                      ->on('payments')
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
        $this->schema->dropIfExists('payment_details');
    }
}
