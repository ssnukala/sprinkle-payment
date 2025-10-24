# UserFrosting 6 Payment Sprinkle

A comprehensive payment processing sprinkle for UserFrosting 6, providing integration with multiple payment gateways including PayPal, Stripe, Apple Pay, Google Pay, and manual check payments.

## Features

- **Multiple Payment Gateways**: Stripe, PayPal, Apple Pay, Google Pay, and Manual Check payments
- **Order Management**: Complete order and order line item management
- **Payment Tracking**: Track payments with detailed status and transaction information
- **Refund Support**: Process refunds through supported payment gateways
- **Frontend Widgets**: Ready-to-use JavaScript widgets for payment processing
- **RESTful API**: Complete API for order and payment management
- **Database Migrations**: Automatic database schema setup
- **Flexible Architecture**: Easy to extend with additional payment processors

## Installation

1. Install via Composer:

```bash
composer require ssnukala/sprinkle-payment
```

2. Register the sprinkle in your UserFrosting application's sprinkle list.

3. Run migrations:

```bash
php bakery migrate
```

4. Configure your payment gateway credentials in your `.env` file:

```env
# Stripe
STRIPE_PUBLIC_KEY=your_stripe_public_key
STRIPE_SECRET_KEY=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

# PayPal
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_MODE=sandbox

# Apple Pay
APPLE_PAY_MERCHANT_ID=your_apple_pay_merchant_id
APPLE_PAY_CERTIFICATE_PATH=/path/to/certificate

# Google Pay
GOOGLE_PAY_MERCHANT_ID=your_google_pay_merchant_id
GOOGLE_PAY_MERCHANT_NAME=Your Business Name
```

## Database Schema

### Tables

- **orders**: Stores order information
- **order_lines**: Stores individual line items for each order
- **payments**: Stores payment transactions (one order can have multiple payments)
- **payment_details**: Stores detailed information about each payment transaction

## Usage

### Creating an Order via API

```bash
POST /api/payment/orders
Content-Type: application/json

{
  "user_id": 1,
  "line_items": [
    {
      "item_type": "product",
      "item_name": "Sample Product",
      "quantity": 2,
      "unit_price": 50.00
    }
  ],
  "shipping": 10.00,
  "tax": 8.00
}
```

### Processing a Payment

```bash
POST /api/payment/payments
Content-Type: application/json

{
  "order_id": 1,
  "payment_method": "stripe",
  "amount": 118.00,
  "stripe_token": "tok_visa"
}
```

### Using the Payment Widget

Include the payment widget in your Twig template:

```twig
{% include '@payment/components/payment/widget.html.twig' with {
    'order_id': order.id,
    'amount': order.total,
    'currency': order.currency,
    'enabled_methods': ['stripe', 'paypal', 'manual_check']
} %}
```

Or use it directly with JavaScript:

```javascript
$('#payment-container').paymentWidget({
    orderId: 123,
    amount: 118.00,
    currency: 'USD',
    enabledMethods: ['stripe', 'paypal', 'manual_check'],
    stripePublicKey: 'pk_test_...',
    onSuccess: function(payment) {
        console.log('Payment successful:', payment);
    }
});
```

## API Endpoints

### Orders

- `POST /api/payment/orders` - Create a new order
- `GET /api/payment/orders` - List orders (filter by user_id or status)
- `GET /api/payment/orders/{id}` - Get order details

### Payments

- `POST /api/payment/payments` - Process a payment
- `GET /api/payment/payments` - List payments (filter by order_id, status, or method)
- `GET /api/payment/payments/{id}` - Get payment details
- `POST /api/payment/payments/{id}/refund` - Refund a payment

## Payment Methods

### Stripe

Supports credit/debit card payments via Stripe. Requires Stripe.js on the frontend.

### PayPal

Supports PayPal payments with redirect flow. Requires PayPal SDK.

### Apple Pay

Supports Apple Pay via Stripe or another backend processor.

### Google Pay

Supports Google Pay via Stripe or another backend processor.

### Manual Check

For offline check payments with manual verification.

## Extending Payment Processors

To add a new payment processor, create a class implementing `PaymentProcessorInterface`:

```php
<?php

namespace YourNamespace;

use UserFrosting\Sprinkle\Payment\Services\PaymentProcessorInterface;
use UserFrosting\Sprinkle\Payment\Database\Models\Payment;

class CustomProcessor implements PaymentProcessorInterface
{
    public function process(Payment $payment, array $data): array
    {
        // Your processing logic
        return ['success' => true, 'transaction_id' => '...'];
    }

    public function refund(Payment $payment, float $amount): array
    {
        // Your refund logic
        return ['success' => true];
    }

    public function verify(Payment $payment): array
    {
        // Your verification logic
        return ['success' => true, 'status' => 'completed'];
    }
}
```

## Testing

Run the test suite:

```bash
composer test
```

Run PHPStan static analysis:

```bash
composer phpstan
```

Run PHP CS Fixer:

```bash
composer phpcs
```

## License

MIT License - see LICENSE file for details

## Credits

Created by [Srinivas Nukala](https://srinivasnukala.com)

Based on UserFrosting 6 sprinkle conventions and inspired by sprinkle-crud6.

## Reference

This sprinkle is built following UserFrosting 6 conventions and references:
- UserFrosting v4.6.7 sprinkles: [ufsprinkle-payment](https://github.com/ssnukala/ufsprinkle-payment) and [ufsprinkle-orders](https://github.com/ssnukala/ufsprinkle-orders)
- [sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6) for UserFrosting 6 structure
