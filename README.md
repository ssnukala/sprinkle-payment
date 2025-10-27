# UserFrosting 6 Payment Sprinkle

A comprehensive payment processing sprinkle for UserFrosting 6, providing integration with multiple payment gateways including PayPal, Stripe, Apple Pay, Google Pay, and manual check payments.

Built on top of **[sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6)** for robust CRUD operations and API layer, and **[sprinkle-orders](https://github.com/ssnukala/sprinkle-orders)** for order management. Uses official JavaScript/TypeScript SDKs from all payment providers.

## Architecture

This sprinkle follows a **schema-driven architecture** using CRUD6:

- **No Custom Model Classes**: All models are defined via JSON schemas in `app/schema/crud6/`
- **Generic CRUD Operations**: Full CRUD via CRUD6's SchemaService
- **Order Management**: Depends on `sprinkle-orders` for `sales_order` and `sales_order_lines` models
- **Business Logic in Services**: Payment processing logic in `PaymentService`, not in models
- **RESTful APIs**: Auto-generated CRUD6 endpoints + custom payment endpoints

### Dependencies

- **sprinkle-crud6**: Generic CRUD operations and schema-driven models
- **sprinkle-orders**: Order and order line management
- **Payment Gateways**: Stripe, PayPal SDKs

## Features

- **Multiple Payment Gateways**: Stripe, PayPal, Apple Pay, Google Pay, and Manual Check payments
- **CRUD6 Integration**: Leverages sprinkle-crud6 for both frontend and API layer
- **Official SDKs**: Uses official JavaScript/TypeScript libraries:
  - Stripe.js for Stripe payments
  - PayPal JavaScript SDK for PayPal
  - Payment Request API for Apple Pay and Google Pay
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

2. The sprinkle automatically includes `ssnukala/sprinkle-crud6` and `ssnukala/sprinkle-orders` as dependencies.

3. Register the sprinkle in your UserFrosting application's sprinkle list.

4. Run migrations:

```bash
php bakery migrate
```

5. Configure your payment gateway credentials in your `.env` file:

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

This sprinkle provides:
- **payments**: Stores payment transactions (one order can have multiple payments)
- **payment_details**: Stores detailed information about each payment transaction

Order tables are provided by **sprinkle-orders**:
- **sales_order**: Order information (from sprinkle-orders)
- **sales_order_lines**: Order line items (from sprinkle-orders)

All models are defined via CRUD6 JSON schemas in `app/schema/crud6/`.

## CRUD6 Integration

This sprinkle is built on top of [sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6), which provides:

- **Generic CRUD API Layer**: Standardized REST API patterns
- **JSON Schema Support**: Data validation and structure
- **Schema-Driven Models**: No custom Eloquent model classes needed
- **Automatic Relationships**: Via detail sections in schemas
- **Extensibility**: Easy to extend with custom business logic

### Using CRUD6 with Payments

```php
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

// Get payment model instance
$paymentModel = $schemaService->getModelInstance('payment');

// Query payments
$payments = $paymentModel->where('status', 'CO')->get();

// Create payment
$payment = $paymentModel->create([
    'order_id' => $orderId,
    'payment_method' => 'ST',
    'amount' => 100.00,
    'status' => 'PP',
]);

// Get payment details via CRUD6 detail section
// GET /api/crud6/payment/{id}/details
```

### Using Orders from sprinkle-orders

```php
// Get order model from sprinkle-orders
$orderModel = $schemaService->getModelInstance('sales_order');
$order = $orderModel->find($orderId);

// Get order lines via CRUD6 detail section
// GET /api/crud6/sales_order/{id}/lines
```

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

### CRUD6 Auto-Generated Endpoints

**Payments:**
- `GET /api/crud6/payment` - List all payments
- `GET /api/crud6/payment/{id}` - Get payment details
- `POST /api/crud6/payment` - Create a payment
- `PUT /api/crud6/payment/{id}` - Update a payment
- `DELETE /api/crud6/payment/{id}` - Delete a payment
- `GET /api/crud6/payment/{id}/details` - Get payment details (relationship)

**Orders (from sprinkle-orders):**
- `GET /api/crud6/sales_order` - List all orders
- `GET /api/crud6/sales_order/{id}` - Get order details
- `POST /api/crud6/sales_order` - Create an order
- `PUT /api/crud6/sales_order/{id}` - Update an order
- `DELETE /api/crud6/sales_order/{id}` - Delete an order
- `GET /api/crud6/sales_order/{id}/lines` - Get order lines (relationship)

### Custom Payment Processing Endpoints

**Payment Business Logic:**
- `POST /api/payment/payments` - Process a payment (with gateway integration)
- `POST /api/payment/payments/{id}/refund` - Refund a payment
- `GET /api/payment/orders` - List orders (custom filtering)
- `GET /api/payment/payments` - List payments (custom filtering)

**Note:** Use CRUD6 endpoints for basic CRUD operations, and custom endpoints for payment-specific business logic.

## Payment Methods & Official SDKs

This sprinkle uses **official JavaScript/TypeScript libraries** from each payment provider for maximum security and compatibility.

### Stripe

**Uses**: [Stripe.js](https://stripe.com/docs/js) - Official Stripe JavaScript library

- Secure card element rendering
- PCI compliance built-in
- Automatic validation
- Payment Method API for modern payments

The widget automatically loads Stripe.js from `https://js.stripe.com/v3/` when Stripe is enabled.

**Example**:
```javascript
$('#payment-container').paymentWidget({
    enabledMethods: ['stripe'],
    stripePublicKey: 'pk_test_...'
});
```

### PayPal

**Uses**: [PayPal JavaScript SDK](https://developer.paypal.com/sdk/js/) - Official PayPal SDK

- Smart Payment Buttons
- Secure checkout flow
- One-touch payments
- Automatic currency handling

The widget loads the PayPal SDK with your client ID: `https://www.paypal.com/sdk/js?client-id={YOUR_CLIENT_ID}`

**Example**:
```javascript
$('#payment-container').paymentWidget({
    enabledMethods: ['paypal'],
    paypalClientId: 'your-paypal-client-id'
});
```

### Apple Pay

**Uses**: [Payment Request API](https://developer.apple.com/documentation/apple_pay_on_the_web) - W3C Standard + Apple Pay

- Native Apple Pay integration
- Secure tokenization
- Touch ID / Face ID authentication
- Works in Safari on iOS and macOS

**Example**:
```javascript
$('#payment-container').paymentWidget({
    enabledMethods: ['apple_pay'],
    appleMerchantId: 'merchant.com.yourcompany'
});
```

### Google Pay

**Uses**: [Payment Request API](https://developers.google.com/pay/api/web) - W3C Standard + Google Pay API

- Secure Google Pay integration
- Tokenized card data
- Biometric authentication support
- Works across Chrome browsers

**Example**:
```javascript
$('#payment-container').paymentWidget({
    enabledMethods: ['google_pay'],
    googleMerchantId: 'your-merchant-id',
    googleMerchantName: 'Your Store',
    googlePayGateway: 'stripe',
    googleGatewayMerchantId: 'your-stripe-merchant-id'
});
```

### Manual Check

For offline check payments with manual verification. No external SDK required.

## CRUD6 Integration

This sprinkle is built on top of [sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6), which provides:

- **Generic CRUD API Layer**: Standardized REST API patterns
- **JSON Schema Support**: Data validation and structure
- **Frontend Integration**: Consistent UI patterns and components
- **Extensibility**: Easy to extend with custom business logic

The payment sprinkle extends CRUD6 capabilities with payment-specific features while maintaining compatibility with the CRUD6 architecture.

## Extending Payment Processors

To add a new payment processor, create a class implementing `PaymentProcessorInterface`:

```php
<?php

namespace YourNamespace;

use UserFrosting\Sprinkle\Payment\Services\PaymentProcessorInterface;
use Illuminate\Database\Eloquent\Model;

class CustomProcessor implements PaymentProcessorInterface
{
    public function process(Model $payment, array $data): array
    {
        // Your processing logic
        // $payment is a CRUD6 model instance
        // Access fields: $payment->amount, $payment->order_id, etc.
        
        return ['success' => true, 'transaction_id' => '...'];
    }

    public function refund(Model $payment, float $amount): array
    {
        // Your refund logic
        return ['success' => true];
    }

    public function verify(Model $payment): array
    {
        // Your verification logic
        return ['success' => true, 'status' => 'completed'];
    }
}
```

**Note:** All payment processors receive CRUD6 `Model` instances, not custom model classes.

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
