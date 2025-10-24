/**
 * UserFrosting Payment Widget
 * 
 * Provides a frontend widget for processing payments with multiple payment methods
 * Uses official payment provider JavaScript/TypeScript libraries
 */

(function($) {
    'use strict';

    /**
     * Payment Widget Class
     * Integrates with official payment provider SDKs:
     * - Stripe.js for Stripe payments
     * - PayPal JavaScript SDK for PayPal payments
     * - Payment Request API for Apple Pay and Google Pay
     */
    class PaymentWidget {
        constructor(element, options) {
            this.element = $(element);
            this.options = $.extend({}, PaymentWidget.DEFAULTS, options);
            this.init();
        }

        init() {
            this.setupUI();
            this.bindEvents();
            this.loadPaymentSDKs();
        }

        setupUI() {
            const template = `
                <div class="payment-widget">
                    <div class="payment-methods">
                        <h4>Select Payment Method</h4>
                        <div class="payment-method-list">
                            ${this.options.enabledMethods.map(method => this.getMethodButton(method)).join('')}
                        </div>
                    </div>
                    <div class="payment-form" style="display: none;">
                        <div class="payment-amount">
                            <label>Amount:</label>
                            <span class="amount-display">${this.options.currency} 0.00</span>
                        </div>
                        <div class="payment-details" id="payment-details">
                            <!-- Payment method specific form will load here -->
                        </div>
                        <div class="payment-actions">
                            <button type="button" class="btn btn-secondary btn-back">Back</button>
                            <button type="button" class="btn btn-primary btn-pay">Pay Now</button>
                        </div>
                    </div>
                    <div class="payment-result" style="display: none;">
                        <div class="alert"></div>
                    </div>
                </div>
            `;
            this.element.html(template);
        }

        getMethodButton(method) {
            const labels = {
                'stripe': 'Credit/Debit Card (Stripe)',
                'paypal': 'PayPal',
                'apple_pay': 'Apple Pay',
                'google_pay': 'Google Pay',
                'manual_check': 'Check Payment'
            };

            return `
                <button type="button" class="btn btn-outline-primary payment-method-btn" data-method="${method}">
                    <i class="payment-icon payment-icon-${method}"></i>
                    ${labels[method] || method}
                </button>
            `;
        }

        bindEvents() {
            const self = this;

            // Payment method selection
            this.element.on('click', '.payment-method-btn', function() {
                const method = $(this).data('method');
                self.selectPaymentMethod(method);
            });

            // Back button
            this.element.on('click', '.btn-back', function() {
                self.showPaymentMethods();
            });

            // Pay button
            this.element.on('click', '.btn-pay', function() {
                self.processPayment();
            });
        }

        /**
         * Load official payment provider SDKs
         */
        loadPaymentSDKs() {
            // Load Stripe.js if enabled and not already loaded
            if (this.options.enabledMethods.includes('stripe') && this.options.stripePublicKey) {
                if (typeof Stripe === 'undefined') {
                    this.loadScript('https://js.stripe.com/v3/', () => {
                        this.initStripe();
                    });
                } else {
                    this.initStripe();
                }
            }

            // Load PayPal SDK if enabled
            if (this.options.enabledMethods.includes('paypal') && this.options.paypalClientId) {
                if (typeof paypal === 'undefined') {
                    const paypalUrl = `https://www.paypal.com/sdk/js?client-id=${this.options.paypalClientId}&currency=${this.options.currency}`;
                    this.loadScript(paypalUrl, () => {
                        console.log('PayPal SDK loaded');
                    });
                }
            }
        }

        /**
         * Load external script dynamically
         */
        loadScript(src, callback) {
            const script = document.createElement('script');
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        }

        /**
         * Initialize Stripe.js
         */
        initStripe() {
            if (typeof Stripe !== 'undefined' && this.options.stripePublicKey) {
                this.stripe = Stripe(this.options.stripePublicKey);
                this.stripeElements = this.stripe.elements();
            }
        }

        selectPaymentMethod(method) {
            this.currentMethod = method;
            this.element.find('.payment-methods').hide();
            this.element.find('.payment-form').show();
            this.element.find('.amount-display').text(`${this.options.currency} ${this.options.amount.toFixed(2)}`);
            this.loadPaymentForm(method);
        }

        loadPaymentForm(method) {
            const detailsContainer = this.element.find('#payment-details');
            detailsContainer.empty();

            switch(method) {
                case 'stripe':
                    this.loadStripeForm(detailsContainer);
                    break;
                case 'paypal':
                    this.loadPayPalForm(detailsContainer);
                    break;
                case 'apple_pay':
                    this.loadApplePayForm(detailsContainer);
                    break;
                case 'google_pay':
                    this.loadGooglePayForm(detailsContainer);
                    break;
                case 'manual_check':
                    this.loadManualCheckForm(detailsContainer);
                    break;
            }
        }

        /**
         * Load Stripe payment form using official Stripe Elements
         */
        loadStripeForm(container) {
            if (!this.stripe) {
                container.html('<div class="alert alert-danger">Stripe is not configured. Please add your Stripe public key.</div>');
                return;
            }

            container.html(`
                <div class="stripe-payment-form">
                    <div id="stripe-card-element" class="form-control"></div>
                    <div id="stripe-card-errors" class="text-danger mt-2" role="alert"></div>
                </div>
            `);

            // Create Stripe Card Element using official API
            this.stripeCard = this.stripeElements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#32325d',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a'
                    }
                }
            });
            
            this.stripeCard.mount('#stripe-card-element');

            // Handle real-time validation errors
            this.stripeCard.on('change', function(event) {
                const displayError = document.getElementById('stripe-card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
        }

        /**
         * Load PayPal payment form using official PayPal SDK
         */
        loadPayPalForm(container) {
            if (typeof paypal === 'undefined') {
                container.html('<div class="alert alert-danger">PayPal SDK is not loaded. Please check your configuration.</div>');
                return;
            }

            container.html(`
                <div class="paypal-payment-form">
                    <p class="mb-3">Click the PayPal button below to complete your payment securely.</p>
                    <div id="paypal-button-container"></div>
                </div>
            `);

            const self = this;

            // Render PayPal button using official SDK
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: self.options.amount.toFixed(2),
                                currency_code: self.options.currency
                            },
                            description: `Order #${self.options.orderId || 'N/A'}`
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        self.handlePayPalSuccess(details);
                    });
                },
                onError: function(err) {
                    self.showError('PayPal payment failed: ' + err.message);
                }
            }).render('#paypal-button-container');

            // Hide the default Pay Now button when PayPal is selected
            this.element.find('.btn-pay').hide();
        }

        /**
         * Load Apple Pay form using Payment Request API
         */
        loadApplePayForm(container) {
            if (!window.PaymentRequest) {
                container.html('<div class="alert alert-danger">Apple Pay is not supported in this browser.</div>');
                return;
            }

            container.html(`
                <div class="apple-pay-form">
                    <p class="mb-3">Click "Pay Now" to proceed with Apple Pay</p>
                    <div id="apple-pay-button" class="apple-pay-button"></div>
                </div>
            `);

            // Apple Pay implementation using Payment Request API
            const paymentRequest = new PaymentRequest(
                [{
                    supportedMethods: 'https://apple.com/apple-pay',
                    data: {
                        version: 3,
                        merchantIdentifier: this.options.appleMerchantId,
                        merchantCapabilities: ['supports3DS'],
                        supportedNetworks: ['visa', 'masterCard', 'amex', 'discover'],
                        countryCode: 'US'
                    }
                }],
                {
                    total: {
                        label: 'Total',
                        amount: {
                            currency: this.options.currency,
                            value: this.options.amount.toFixed(2)
                        }
                    }
                }
            );

            this.applePayRequest = paymentRequest;
        }

        /**
         * Load Google Pay form using official Google Pay API
         */
        loadGooglePayForm(container) {
            if (!window.PaymentRequest) {
                container.html('<div class="alert alert-danger">Google Pay is not supported in this browser.</div>');
                return;
            }

            container.html(`
                <div class="google-pay-form">
                    <p class="mb-3">Click "Pay Now" to proceed with Google Pay</p>
                    <div id="google-pay-button"></div>
                </div>
            `);

            // Google Pay implementation using Payment Request API
            const paymentRequest = new PaymentRequest(
                [{
                    supportedMethods: 'https://google.com/pay',
                    data: {
                        environment: this.options.googlePayEnvironment || 'TEST',
                        apiVersion: 2,
                        apiVersionMinor: 0,
                        merchantInfo: {
                            merchantId: this.options.googleMerchantId,
                            merchantName: this.options.googleMerchantName
                        },
                        allowedPaymentMethods: [{
                            type: 'CARD',
                            parameters: {
                                allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                                allowedCardNetworks: ['AMEX', 'DISCOVER', 'MASTERCARD', 'VISA']
                            },
                            tokenizationSpecification: {
                                type: 'PAYMENT_GATEWAY',
                                parameters: {
                                    gateway: this.options.googlePayGateway || 'stripe',
                                    gatewayMerchantId: this.options.googleGatewayMerchantId
                                }
                            }
                        }]
                    }
                }],
                {
                    total: {
                        label: 'Total',
                        amount: {
                            currency: this.options.currency,
                            value: this.options.amount.toFixed(2)
                        }
                    }
                }
            );

            this.googlePayRequest = paymentRequest;
        }

        loadManualCheckForm(container) {
            container.html(`
                <div class="manual-check-form">
                    <div class="form-group">
                        <label>Check Number</label>
                        <input type="text" class="form-control" id="check-number" required>
                    </div>
                    <div class="form-group">
                        <label>Check Date</label>
                        <input type="date" class="form-control" id="check-date" required>
                    </div>
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" class="form-control" id="bank-name">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" id="check-notes"></textarea>
                    </div>
                </div>
            `);
        }

        async processPayment() {
            const self = this;
            const payButton = this.element.find('.btn-pay');
            payButton.prop('disabled', true).text('Processing...');

            try {
                let paymentData = {
                    order_id: this.options.orderId,
                    payment_method: this.currentMethod,
                    amount: this.options.amount
                };

                // Collect method-specific data using official SDKs
                if (this.currentMethod === 'stripe') {
                    const stripeResult = await this.processStripePayment();
                    if (!stripeResult.success) {
                        throw new Error(stripeResult.error);
                    }
                    paymentData = {...paymentData, ...stripeResult.data};
                } else if (this.currentMethod === 'apple_pay') {
                    const applePayResult = await this.processApplePay();
                    if (!applePayResult.success) {
                        throw new Error(applePayResult.error);
                    }
                    paymentData = {...paymentData, ...applePayResult.data};
                } else if (this.currentMethod === 'google_pay') {
                    const googlePayResult = await this.processGooglePay();
                    if (!googlePayResult.success) {
                        throw new Error(googlePayResult.error);
                    }
                    paymentData = {...paymentData, ...googlePayResult.data};
                } else if (this.currentMethod === 'manual_check') {
                    paymentData.check_number = $('#check-number').val();
                    paymentData.check_date = $('#check-date').val();
                    paymentData.bank_name = $('#bank-name').val();
                    paymentData.notes = $('#check-notes').val();
                }

                // Send payment request
                const response = await $.ajax({
                    url: this.options.apiEndpoint,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(paymentData)
                });

                if (response.success) {
                    this.showSuccess('Payment processed successfully!');
                    if (this.options.onSuccess) {
                        this.options.onSuccess(response.payment);
                    }
                } else {
                    this.showError(response.error || 'Payment failed');
                }
            } catch (error) {
                this.showError(error.message || 'An error occurred');
            } finally {
                payButton.prop('disabled', false).text('Pay Now');
            }
        }

        /**
         * Process Stripe payment using official Stripe.js API
         */
        async processStripePayment() {
            if (!this.stripe || !this.stripeCard) {
                return {success: false, error: 'Stripe is not initialized'};
            }

            try {
                // Create payment method using Stripe.js
                const {paymentMethod, error} = await this.stripe.createPaymentMethod({
                    type: 'card',
                    card: this.stripeCard,
                });
                
                if (error) {
                    return {success: false, error: error.message};
                }

                return {
                    success: true,
                    data: {
                        payment_method_id: paymentMethod.id
                    }
                };
            } catch (error) {
                return {success: false, error: error.message};
            }
        }

        /**
         * Handle PayPal payment success
         */
        handlePayPalSuccess(details) {
            $.ajax({
                url: this.options.apiEndpoint,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    order_id: this.options.orderId,
                    payment_method: 'paypal',
                    amount: this.options.amount,
                    paypal_order_id: details.id,
                    payer_id: details.payer.payer_id
                })
            }).done((response) => {
                if (response.success) {
                    this.showSuccess('Payment processed successfully!');
                    if (this.options.onSuccess) {
                        this.options.onSuccess(response.payment);
                    }
                } else {
                    this.showError(response.error || 'Payment failed');
                }
            }).fail(() => {
                this.showError('Failed to process PayPal payment');
            });
        }

        /**
         * Process Apple Pay using Payment Request API
         */
        async processApplePay() {
            if (!this.applePayRequest) {
                return {success: false, error: 'Apple Pay is not initialized'};
            }

            try {
                const paymentResponse = await this.applePayRequest.show();
                const paymentToken = paymentResponse.details.token;
                
                await paymentResponse.complete('success');

                return {
                    success: true,
                    data: {
                        payment_token: JSON.stringify(paymentToken)
                    }
                };
            } catch (error) {
                return {success: false, error: error.message};
            }
        }

        /**
         * Process Google Pay using Payment Request API
         */
        async processGooglePay() {
            if (!this.googlePayRequest) {
                return {success: false, error: 'Google Pay is not initialized'};
            }

            try {
                const paymentResponse = await this.googlePayRequest.show();
                const paymentToken = paymentResponse.details.paymentMethodData.tokenizationData.token;
                
                await paymentResponse.complete('success');

                return {
                    success: true,
                    data: {
                        payment_token: paymentToken
                    }
                };
            } catch (error) {
                return {success: false, error: error.message};
            }
        }

        showSuccess(message) {
            this.element.find('.payment-form').hide();
            this.element.find('.payment-result').show();
            this.element.find('.payment-result .alert')
                .removeClass('alert-danger')
                .addClass('alert-success')
                .text(message);
        }

        showError(message) {
            this.element.find('.payment-result').show();
            this.element.find('.payment-result .alert')
                .removeClass('alert-success')
                .addClass('alert-danger')
                .text(message);
        }

        showPaymentMethods() {
            this.element.find('.payment-form').hide();
            this.element.find('.payment-result').hide();
            this.element.find('.payment-methods').show();
            this.element.find('.btn-pay').show(); // Show pay button again
        }
    }

    PaymentWidget.DEFAULTS = {
        orderId: null,
        amount: 0,
        currency: 'USD',
        enabledMethods: ['stripe', 'paypal', 'manual_check'],
        stripePublicKey: null,
        paypalClientId: null,
        appleMerchantId: null,
        googleMerchantId: null,
        googleMerchantName: null,
        googlePayEnvironment: 'TEST',
        googlePayGateway: 'stripe',
        googleGatewayMerchantId: null,
        apiEndpoint: '/api/payment/payments',
        onSuccess: null,
        onError: null
    };

    // jQuery plugin
    $.fn.paymentWidget = function(options) {
        return this.each(function() {
            const $this = $(this);
            let data = $this.data('uf.payment-widget');

            if (!data) {
                data = new PaymentWidget(this, options);
                $this.data('uf.payment-widget', data);
            }
        });
    };

    // Auto-initialize
    $(document).ready(function() {
        $('[data-widget="payment"]').each(function() {
            const options = $(this).data();
            $(this).paymentWidget(options);
        });
    });

})(jQuery);
