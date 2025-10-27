# sprinkle-payment Development Guidelines

This sprinkle depends on **[sprinkle-crud6](https://github.com/ssnukala/sprinkle-crud6)** (main branch) and **[sprinkle-orders](https://github.com/ssnukala/sprinkle-orders)** (main branch) for order management and CRUD operations.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## 🎯 CRITICAL ARCHITECTURE - Integration with sprinkle-orders and sprinkle-crud6

### Core Dependencies

**This sprinkle integrates with:**
1. **sprinkle-orders** - Provides order models (sales_order, purchase_order) and cart functionality
2. **sprinkle-crud6** - Provides generic CRUD operations via JSON schemas

### What sprinkle-orders Provides

1. **Order Management**
   - `sales_order` and `sales_order_lines` models (CRUD6 schemas)
   - `purchase_order` and `purchase_order_lines` models (CRUD6 schemas)
   - Mini cart functionality for e-commerce
   - Order workflow and status management

2. **Schema-Driven Architecture**
   - All models defined as CRUD6 JSON schemas
   - No custom Eloquent model classes
   - Relationships via detail sections in schemas
   - API endpoints via CRUD6: `/api/crud6/{model}`

### What sprinkle-crud6 Provides

1. **Generic Model System**
   - `CRUD6Model` - Dynamically configured from JSON schemas
   - Full Eloquent ORM support without custom model classes
   - Automatic type casting, fillable fields, timestamps

2. **RESTful API Endpoints**
   - Automatic routes: `/api/crud6/{model}`
   - Full CRUD operations with filtering, sorting, pagination
   - Relationship management via detail sections

### What This Sprinkle Should Contain

**✅ DO Include:**
- Payment-specific models (Payment, PaymentDetail) as CRUD6 schemas
- Payment processor implementations (Stripe, PayPal, Apple Pay, etc.)
- PaymentService for orchestrating payment workflows
- Integration with sprinkle-orders checkout flow
- Custom business logic for payment processing
- Webhook handlers for payment gateway notifications

**❌ DO NOT Include:**
- Duplicate Order or OrderLine models (use from sprinkle-orders)
- Duplicate CRUD operations (use CRUD6)
- Custom Eloquent model classes for payments (use CRUD6 schemas)

## 🚨 CURRENT STATUS - Refactoring Required

### TODO: Remove Duplicate Models

**High Priority:**
1. ❌ **REMOVE** `app/src/Database/Models/Order.php` (duplicate from sprinkle-orders)
2. ❌ **REMOVE** `app/src/Database/Models/OrderLine.php` (duplicate from sprinkle-orders)
3. ✅ **KEEP** `app/src/Database/Models/Payment.php` (convert to CRUD6 schema)
4. ✅ **KEEP** `app/src/Database/Models/PaymentDetail.php` (convert to CRUD6 schema)

### TODO: Convert to CRUD6 Schema Architecture

**Payment Models Migration:**
1. Create `app/schema/crud6/payment.json` based on PaymentsTable migration
2. Create `app/schema/crud6/payment_detail.json` based on PaymentDetailsTable migration
3. Remove traditional Eloquent model files
4. Update PaymentService to use SchemaService for model instances
5. Add detail section to payment schema for payment_details relationship

**Example Payment Schema Structure:**
```json
{
  "model": "payment",
  "table": "payments",
  "detail": {
    "model": "payment_detail",
    "foreign_key": "payment_id",
    "list_fields": ["detail_type", "key", "value"]
  },
  "fields": {
    "id": {"type": "integer", "auto_increment": true},
    "order_id": {"type": "integer", "required": true},
    "payment_method": {"type": "string", "length": 2},
    "status": {"type": "string", "length": 2},
    "amount": {"type": "decimal"},
    "meta": {"type": "json"}
    // ... other fields
  }
}
```

### Integration with sprinkle-orders

**Order-Payment Relationship:**
```php
// Get order using CRUD6
$salesOrderModel = $schemaService->getModelInstance('sales_order');
$order = $salesOrderModel->find($orderId);

// Create payment linked to order
$paymentModel = $schemaService->getModelInstance('payment');
$payment = $paymentModel->create([
    'order_id' => $order->id,
    'payment_method' => 'ST',  // 2-char code
    'status' => 'PP',           // Pending Payment
    'amount' => $order->gross_amount,
    'meta' => $paymentData
]);
```

**Cart Checkout Integration:**
1. User completes cart in sprinkle-orders
2. Checkout creates sales_order
3. Payment sprinkle processes payment
4. Payment status updates order status
5. Successful payment completes the order

## Payment Processing Architecture

### Status Code Pattern (2-Character Codes)

All status codes are defined in `app/config/payment.php`:

```php
const STATUS_PENDING = 'PP';        // Pending Payment
const STATUS_AUTHORIZED = 'AU';     // Authorized
const STATUS_CAPTURED = 'CA';       // Captured
const STATUS_COMPLETED = 'CO';      // Completed
const STATUS_FAILED = 'FA';         // Failed
const STATUS_REFUNDED = 'RE';       // Refunded
const STATUS_CANCELLED = 'CN';      // Cancelled
```

### Payment Method Code Pattern (2-Character Codes)

All payment method codes are defined in `app/config/payment.php`:

```php
const METHOD_STRIPE = 'ST';         // Stripe
const METHOD_PAYPAL = 'PP';         // PayPal
const METHOD_APPLE_PAY = 'AP';      // Apple Pay
const METHOD_GOOGLE_PAY = 'GP';     // Google Pay
const METHOD_MANUAL_CHECK = 'MC';   // Manual Check
```

### Configuration-Driven Design

**Central Configuration:**
```php
// app/config/payment.php
return [
    'payment' => [
        'enabled_methods' => [
            'ST' => 'stripe',
            'PP' => 'paypal',
            'AP' => 'apple_pay',
            'GP' => 'google_pay',
            'MC' => 'manual_check',
        ],
        'status' => [
            'PP' => 'Pending Payment',
            'AU' => 'Authorized',
            'CA' => 'Captured',
            'CO' => 'Completed',
            'FA' => 'Failed',
            'RE' => 'Refunded',
            'CN' => 'Cancelled',
        ],
        'stripe' => [
            'public_key' => getenv('STRIPE_PUBLIC_KEY'),
            'secret_key' => getenv('STRIPE_SECRET_KEY'),
        ],
        // ... other gateway configs
    ],
];
```

### Payment Service Pattern

```php
class PaymentService
{
    public function __construct(
        protected SchemaService $schemaService,
        protected PaymentConfig $config
    ) {}
    
    public function processPayment(
        int $orderId,
        string $paymentMethod,
        float $amount,
        array $paymentData = []
    ): array {
        // Get order from sprinkle-orders
        $orderModel = $this->schemaService->getModelInstance('sales_order');
        $order = $orderModel->find($orderId);
        
        // Normalize payment method (accepts full names or codes)
        $methodCode = $this->normalizePaymentMethod($paymentMethod);
        
        // Create payment record
        $paymentModel = $this->schemaService->getModelInstance('payment');
        $payment = $paymentModel->create([
            'order_id' => $orderId,
            'payment_method' => $methodCode,
            'status' => 'PP',
            'amount' => $amount,
            'meta' => $paymentData['meta'] ?? null,
        ]);
        
        // Delegate to specific processor
        $processor = $this->getPaymentProcessor($methodCode);
        $result = $processor->process($payment, $paymentData);
        
        // Update payment status
        if ($result['success']) {
            $payment->update([
                'status' => 'CO',
                'transaction_id' => $result['transaction_id'],
                'completed_at' => now(),
            ]);
            
            // Update order status
            $order->update(['payment_type' => $methodCode, 'payment_date' => now()]);
        }
        
        return $result;
    }
    
    protected function normalizePaymentMethod(string $method): string
    {
        // Already a code?
        if (strlen($method) === 2 && ctype_upper($method)) {
            return $method;
        }
        
        // Map full names to codes
        $methodMap = [
            'stripe' => 'ST',
            'paypal' => 'PP',
            'apple_pay' => 'AP',
            'google_pay' => 'GP',
            'manual_check' => 'MC',
        ];
        
        return $methodMap[strtolower($method)] ?? 'MC';
    }
}
```

### Payment Processor Pattern

```php
interface PaymentProcessorInterface
{
    public function process(object $payment, array $data): array;
    public function refund(object $payment, float $amount): array;
    public function verify(object $payment): array;
}

class StripeProcessor implements PaymentProcessorInterface
{
    public function process(object $payment, array $data): array
    {
        // Stripe API integration
        // Return ['success' => true/false, 'transaction_id' => '...', 'status' => 'CO']
    }
}
```

## Database Schema Standards

### Avoid MySQL-Specific Types

- ❌ Don't use `enum` types
- ✅ Use `char(2)` or `string(2)` with config-driven codes
- ✅ Use `json` for flexible metadata storage as `meta` field

### Field Naming Conventions

- Use `meta` not `metadata` for JSON fields
- Use 2-character codes for status and payment_method
- Include timestamps: `created_at`, `updated_at`, `deleted_at`
- Use soft deletes in schemas: `"soft_delete": true`

## UserFrosting 6 Framework Standards

### Reference Repositories

1. **[userfrosting/sprinkle-core (6.0)](https://github.com/userfrosting/sprinkle-core/tree/6.0)**
   - Core services, middleware, base controllers

2. **[userfrosting/sprinkle-admin (6.0)](https://github.com/userfrosting/sprinkle-admin/tree/6.0)**
   - Admin patterns, Sprunje, CRUD operations

3. **[ssnukala/sprinkle-crud6 (main)](https://github.com/ssnukala/sprinkle-crud6)**
   - Generic CRUD, JSON schemas, SchemaService

4. **[ssnukala/sprinkle-orders (main)](https://github.com/ssnukala/sprinkle-orders)**
   - Order models, cart, checkout workflow

### Code Modification Standards

**Service Providers:**
```php
class PaymentServiceProvider implements ServicesProviderInterface
{
    public function register(): array
    {
        return [
            PaymentService::class => \DI\autowire(),
        ];
    }
}
```

**Routes:**
```php
class PaymentRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        $app->group('/api/payments', function (RouteCollectorProxy $group) {
            $group->post('/process', ProcessPaymentAction::class);
            $group->post('/{id}/refund', RefundPaymentAction::class);
        })->add(AuthGuard::class);
    }
}
```

## Testing Standards

- Test with 2-character codes, not full names
- Mock payment gateways in unit tests
- Test status transitions and validations
- Use `RefreshDatabase` trait for database tests

## Security Considerations

- Store API keys in environment variables
- Use HTTPS for payment gateway communications
- Validate webhook signatures
- Log all payment transactions
- Never store full credit card numbers
- Use PCI-compliant practices

## Development Workflow

### Bootstrap
```bash
php --version          # Requires PHP 8.1+
composer install       # Install dependencies (15+ min timeout)
php bakery migrate     # Run migrations
```

### Testing
```bash
vendor/bin/phpunit                    # Run all tests
vendor/bin/php-cs-fixer fix           # Format code
vendor/bin/phpstan analyse            # Static analysis
```

### Integration with UserFrosting 6

Add to composer.json:
```json
{
  "require": {
    "ssnukala/sprinkle-crud6": "dev-main",
    "ssnukala/sprinkle-orders": "dev-main",
    "ssnukala/sprinkle-payment": "dev-main"
  },
  "minimum-stability": "beta",
  "prefer-stable": true
}
```

Add to sprinkles array:
```php
return [
    Core::class,
    Account::class,
    Admin::class,
    CRUD6::class,
    Orders::class,    // Must come before Payment
    Payment::class,
];
```

## Refactoring Roadmap

### Phase 1: Remove Duplicate Models ✅
- [x] Identify duplicate Order/OrderLine models
- [x] Document dependencies on sprinkle-orders
- [ ] Remove `app/src/Database/Models/Order.php`
- [ ] Remove `app/src/Database/Models/OrderLine.php`
- [ ] Remove `app/src/Database/Migrations/v100/OrdersTable.php`
- [ ] Remove `app/src/Database/Migrations/v100/OrderLinesTable.php`

### Phase 2: Convert to CRUD6 Schemas
- [ ] Create `app/schema/crud6/payment.json`
- [ ] Create `app/schema/crud6/payment_detail.json`
- [ ] Remove `app/src/Database/Models/Payment.php`
- [ ] Remove `app/src/Database/Models/PaymentDetail.php`
- [ ] Update PaymentService to use SchemaService

### Phase 3: Integration
- [ ] Update composer.json dependencies
- [ ] Integrate with sprinkle-orders checkout
- [ ] Add payment method selection in cart
- [ ] Connect payment status to order status
- [ ] Add webhook handlers for async notifications

### Phase 4: Enhancement
- [ ] Add payment analytics dashboard
- [ ] Implement recurring payments
- [ ] Add multi-currency support
- [ ] Create payment receipt generation
- [ ] Add dispute/chargeback handling

## Common Issues

**Duplicate models:**
- Currently: This sprinkle has duplicate Order/OrderLine models
- Solution: Remove and reference sprinkle-orders models via CRUD6

**Status code mismatches:**
- Always use 2-character codes: 'PP', 'CO', 'FA', etc.
- Frontend can use full names via `normalizePaymentMethod()`

**Payment gateway errors:**
- Check environment variables for API keys
- Verify webhook signature validation
- Check test vs. production mode settings

## Version Compatibility

- **UserFrosting**: ^6.0-beta
- **PHP**: ^8.1
- **sprinkle-core**: ^6.0
- **sprinkle-account**: ^6.0
- **sprinkle-admin**: ^6.0
- **sprinkle-crud6**: dev-main
- **sprinkle-orders**: dev-main
