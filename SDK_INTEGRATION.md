# Payment Provider SDK Integration

This document describes how the UserFrosting Payment Sprinkle integrates with official payment provider SDKs.

## Overview

The payment widget uses **official JavaScript/TypeScript libraries** from each payment provider to ensure:
- Maximum security and PCI compliance
- Latest features and best practices
- Official support and updates
- Reduced maintenance burden

## SDK Loading Strategy

The widget uses **dynamic loading** to only load the SDKs that are actually needed:

1. SDKs are loaded on-demand when the widget initializes
2. Only enabled payment methods trigger SDK loading
3. CDN URLs are used for optimal performance and caching
4. Initialization happens asynchronously

## Stripe Integration

### SDK Used
- **Stripe.js v3**: https://js.stripe.com/v3/

### Features
- Official Stripe Elements for secure card input
- PCI DSS compliance built-in
- Automatic validation and error handling
- Support for Payment Methods API
- SCA (Strong Customer Authentication) ready

### Implementation
```javascript
// SDK is loaded from CDN
const script = 'https://js.stripe.com/v3/';

// Initialize Stripe
this.stripe = Stripe(stripePublicKey);
this.stripeElements = this.stripe.elements();

// Create card element
this.stripeCard = this.stripeElements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#32325d'
        }
    }
});

// Mount to DOM
this.stripeCard.mount('#stripe-card-element');

// Create payment method
const {paymentMethod, error} = await this.stripe.createPaymentMethod({
    type: 'card',
    card: this.stripeCard,
});
```

### Documentation
- [Stripe.js Reference](https://stripe.com/docs/js)
- [Stripe Elements](https://stripe.com/docs/stripe-js/elements)
- [Payment Methods API](https://stripe.com/docs/payments/payment-methods)

## PayPal Integration

### SDK Used
- **PayPal JavaScript SDK**: https://www.paypal.com/sdk/js

### Features
- Smart Payment Buttons
- Automatic currency handling
- One-touch payments
- Guest checkout support
- Mobile-optimized UI

### Implementation
```javascript
// SDK is loaded with client ID and currency
const paypalUrl = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=${currency}`;

// Render PayPal buttons
paypal.Buttons({
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                amount: {
                    value: amount.toFixed(2),
                    currency_code: currency
                }
            }]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            // Handle successful payment
        });
    }
}).render('#paypal-button-container');
```

### Documentation
- [PayPal JavaScript SDK](https://developer.paypal.com/sdk/js/)
- [Smart Payment Buttons](https://developer.paypal.com/docs/checkout/)
- [Orders API](https://developer.paypal.com/docs/api/orders/v2/)

## Apple Pay Integration

### SDK Used
- **Payment Request API** (W3C Standard)
- **Apple Pay JS**: Built into Safari

### Features
- Native Apple Pay experience
- Touch ID / Face ID authentication
- Secure tokenization
- Works on Safari (iOS and macOS)

### Implementation
```javascript
// Use Payment Request API
const paymentRequest = new PaymentRequest(
    [{
        supportedMethods: 'https://apple.com/apple-pay',
        data: {
            version: 3,
            merchantIdentifier: appleMerchantId,
            merchantCapabilities: ['supports3DS'],
            supportedNetworks: ['visa', 'masterCard', 'amex'],
            countryCode: 'US'
        }
    }],
    {
        total: {
            label: 'Total',
            amount: {
                currency: 'USD',
                value: amount.toFixed(2)
            }
        }
    }
);

// Show payment sheet
const paymentResponse = await paymentRequest.show();
const paymentToken = paymentResponse.details.token;
await paymentResponse.complete('success');
```

### Documentation
- [Apple Pay on the Web](https://developer.apple.com/documentation/apple_pay_on_the_web)
- [Payment Request API](https://www.w3.org/TR/payment-request/)
- [Apple Pay JS API](https://developer.apple.com/documentation/apple_pay_on_the_web/apple_pay_js_api)

## Google Pay Integration

### SDK Used
- **Payment Request API** (W3C Standard)
- **Google Pay API**: https://pay.google.com/gp/p/js/pay.js

### Features
- Secure Google Pay integration
- Tokenized payment data
- Biometric authentication
- Works across Chrome browsers
- Gateway integration (Stripe, Braintree, etc.)

### Implementation
```javascript
// Use Payment Request API
const paymentRequest = new PaymentRequest(
    [{
        supportedMethods: 'https://google.com/pay',
        data: {
            environment: 'TEST',
            apiVersion: 2,
            apiVersionMinor: 0,
            merchantInfo: {
                merchantId: googleMerchantId,
                merchantName: merchantName
            },
            allowedPaymentMethods: [{
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                    allowedCardNetworks: ['AMEX', 'MASTERCARD', 'VISA']
                },
                tokenizationSpecification: {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        gateway: 'stripe',
                        gatewayMerchantId: gatewayMerchantId
                    }
                }
            }]
        }
    }],
    {
        total: {
            label: 'Total',
            amount: {
                currency: 'USD',
                value: amount.toFixed(2)
            }
        }
    }
);

// Show payment sheet
const paymentResponse = await paymentRequest.show();
const paymentToken = paymentResponse.details.paymentMethodData.tokenizationData.token;
await paymentResponse.complete('success');
```

### Documentation
- [Google Pay Web](https://developers.google.com/pay/api/web)
- [Payment Request API](https://developers.google.com/pay/api/web/guides/paymentrequest/tutorial)
- [Gateway Integration](https://developers.google.com/pay/api/web/guides/resources/payment-data-cryptography)

## Security Considerations

### Best Practices
1. **Never handle raw card data**: All SDKs tokenize sensitive data
2. **Use HTTPS**: Required for all payment SDKs
3. **Validate on server**: Always verify payments server-side
4. **Keep SDKs updated**: Use CDN versions for automatic updates
5. **PCI Compliance**: Official SDKs maintain compliance

### Token Flow
1. User enters payment information
2. SDK tokenizes data client-side
3. Token sent to your server
4. Server processes with payment gateway
5. Payment confirmation returned

## Browser Support

| Provider | Chrome | Safari | Firefox | Edge |
|----------|--------|--------|---------|------|
| Stripe   | ✅     | ✅     | ✅      | ✅   |
| PayPal   | ✅     | ✅     | ✅      | ✅   |
| Apple Pay| ❌     | ✅     | ❌      | ❌   |
| Google Pay| ✅    | ❌     | ❌      | ✅   |

## Troubleshooting

### Stripe not loading
- Check that `stripePublicKey` is set
- Verify HTTPS is being used
- Check browser console for errors

### PayPal button not rendering
- Verify `paypalClientId` is correct
- Check that container element exists
- Ensure SDK loaded before rendering

### Apple Pay not available
- Only works in Safari on iOS/macOS
- Requires valid merchant ID
- Must be served over HTTPS

### Google Pay not showing
- Only works in Chrome/Chromium browsers
- Requires merchant configuration
- Payment Request API must be supported

## Testing

### Test Credentials

**Stripe Test Cards**:
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- 3D Secure: `4000 0027 6000 3184`

**PayPal Sandbox**:
- Email: See PayPal Developer Dashboard
- Password: See PayPal Developer Dashboard

**Apple Pay**:
- Use Safari in sandbox mode
- Test with development merchant ID

**Google Pay**:
- Set environment to 'TEST'
- Use test cards in Chrome DevTools

## Migration from Custom Implementation

If migrating from a custom payment implementation:

1. Update widget initialization to use official SDKs
2. Replace custom card forms with Stripe Elements
3. Switch PayPal to official button SDK
4. Test each payment method thoroughly
5. Update payment processing endpoints if needed

## Further Reading

- [PCI DSS Compliance](https://www.pcisecuritystandards.org/)
- [Payment Request API Specification](https://www.w3.org/TR/payment-request/)
- [Strong Customer Authentication](https://stripe.com/docs/strong-customer-authentication)
