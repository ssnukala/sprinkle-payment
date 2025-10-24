<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Routes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Sprinkle\Payment\Controller\PaymentController;
use UserFrosting\Routes\RouteDefinitionInterface;

/**
 * Payment Routes
 *
 * Defines routes for payment-related endpoints
 */
class PaymentRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->group('/api/payment', function (RouteCollectorProxy $group) {
            // Order routes
            $group->post('/orders', [PaymentController::class, 'createOrder'])
                  ->setName('api.payment.orders.create');
            
            $group->get('/orders', [PaymentController::class, 'listOrders'])
                  ->setName('api.payment.orders.list');
            
            $group->get('/orders/{id}', [PaymentController::class, 'getOrder'])
                  ->setName('api.payment.orders.get');

            // Payment routes
            $group->post('/payments', [PaymentController::class, 'processPayment'])
                  ->setName('api.payment.payments.process');
            
            $group->get('/payments', [PaymentController::class, 'listPayments'])
                  ->setName('api.payment.payments.list');
            
            $group->get('/payments/{id}', [PaymentController::class, 'getPayment'])
                  ->setName('api.payment.payments.get');
            
            $group->post('/payments/{id}/refund', [PaymentController::class, 'refundPayment'])
                  ->setName('api.payment.payments.refund');
        });
    }
}
