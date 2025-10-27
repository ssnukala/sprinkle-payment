<?php

declare(strict_types=1);

/*
 * UserFrosting Payment Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-payment
 * @copyright Copyright (c) 2025 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-payment/blob/main/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\Payment\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\Payment\Services\PaymentService;
use UserFrosting\Sprinkle\Payment\Database\Repositories\PaymentRepository;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;
use DI\Attribute\Inject;

/**
 * Payment Controller
 *
 * Handles payment-related HTTP requests
 */
class PaymentController
{
    #[Inject]
    protected PaymentService $paymentService;

    #[Inject]
    protected PaymentRepository $paymentRepository;

    #[Inject]
    protected SchemaService $schemaService;

    /**
     * Create a new order
     */
    public function createOrder(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validate required fields
        if (!isset($data['user_id']) || !isset($data['line_items'])) {
            throw new ValidationException('user_id and line_items are required');
        }

        $order = $this->paymentService->createOrder(
            (int)$data['user_id'],
            $data['line_items'],
            $data
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'order' => $order->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get order details
     * Note: Uses sales_order from sprinkle-orders
     */
    public function getOrder(Request $request, Response $response, array $args): Response
    {
        $orderId = (int)$args['id'];
        $orderModel = $this->schemaService->getModelInstance('sales_order');
        $order = $orderModel->find($orderId);

        if (!$order) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Order not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Load relationships via CRUD6 detail section
        // Details available at: GET /api/crud6/sales_order/{id}/lines
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'order' => $order->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Process a payment
     */
    public function processPayment(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Validate required fields
        if (!isset($data['order_id']) || !isset($data['payment_method']) || !isset($data['amount'])) {
            throw new ValidationException('order_id, payment_method, and amount are required');
        }

        $orderModel = $this->schemaService->getModelInstance('sales_order');
        $order = $orderModel->find((int)$data['order_id']);

        if (!$order) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Order not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $payment = $this->paymentService->processPayment(
            $order,
            $data['payment_method'],
            (float)$data['amount'],
            $data
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'payment' => $payment->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Get payment details
     */
    public function getPayment(Request $request, Response $response, array $args): Response
    {
        $paymentId = (int)$args['id'];
        $payment = $this->paymentRepository->find($paymentId);

        if (!$payment) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Payment not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Payment details available via CRUD6 detail section API
        // GET /api/crud6/payment/{id}/details
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'payment' => $payment->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Request $request, Response $response, array $args): Response
    {
        $paymentId = (int)$args['id'];
        $data = $request->getParsedBody();

        $payment = $this->paymentRepository->find($paymentId);

        if (!$payment) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Payment not found',
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $amount = isset($data['amount']) ? (float)$data['amount'] : null;
        $success = $this->paymentService->refundPayment($payment, $amount);

        $response->getBody()->write(json_encode([
            'success' => $success,
            'payment' => $payment->fresh()->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * List orders
     * Note: Uses sales_order from sprinkle-orders
     */
    public function listOrders(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $orderModel = $this->schemaService->getModelInstance('sales_order');
        
        if (isset($params['user_id'])) {
            $orders = $orderModel->where('user_id', (int)$params['user_id'])
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif (isset($params['status'])) {
            $orders = $orderModel->where('status', $params['status'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Return empty for now, implement pagination later
            $orders = collect([]);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'orders' => $orders->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * List payments
     */
    public function listPayments(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        if (isset($params['order_id'])) {
            $payments = $this->paymentRepository->getByOrder((int)$params['order_id']);
        } elseif (isset($params['status'])) {
            $payments = $this->paymentRepository->getByStatus($params['status']);
        } elseif (isset($params['payment_method'])) {
            $payments = $this->paymentRepository->getByMethod($params['payment_method']);
        } else {
            // Return empty for now, implement pagination later
            $payments = collect([]);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'payments' => $payments->toArray(),
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
