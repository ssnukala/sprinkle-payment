<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment;

use UserFrosting\Event\EventListenerRecipe;
use UserFrosting\Sprinkle\Account\Account;
use UserFrosting\Sprinkle\Admin\Admin;
use UserFrosting\Sprinkle\Core\Core;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\Payment\Database\Migrations\v100\OrdersTable;
use UserFrosting\Sprinkle\Payment\Database\Migrations\v100\OrderLinesTable;
use UserFrosting\Sprinkle\Payment\Database\Migrations\v100\PaymentsTable;
use UserFrosting\Sprinkle\Payment\Database\Migrations\v100\PaymentDetailsTable;

class Payment implements SprinkleRecipe
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Payment Sprinkle';
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        return __DIR__ . '/../';
    }

    /**
     * {@inheritDoc}
     */
    public function getSprinkles(): array
    {
        return [
            Core::class,
            Account::class,
            Admin::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutes(): array
    {
        return [
            Routes\PaymentRoutes::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getServices(): array
    {
        return [
            Services\PaymentService::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getMigrations(): array
    {
        return [
            OrdersTable::class,
            OrderLinesTable::class,
            PaymentsTable::class,
            PaymentDetailsTable::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners(): array
    {
        return [];
    }
}
