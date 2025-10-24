/**
 * UserFrosting Payment Widget
 * 
 * Provides a frontend widget for processing payments with multiple payment methods
 */

(function($) {
    'use strict';

    /**
     * Payment Widget Class
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
            this.loadPaymentMethods();
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

        loadPaymentMethods() {
            // Initialize payment gateways if needed
            if (this.options.enabledMethods.includes('stripe') && this.options.stripePublicKey) {
                this.initStripe();
            }
        }

        initStripe() {
            if (typeof Stripe !== 'undefined') {
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

        loadStripeForm(container) {
            if (!this.stripe) {
                container.html('<div class="alert alert-danger">Stripe is not configured</div>');
                return;
            }

            container.html('<div id="stripe-card-element" class="form-control"></div>');
            this.stripeCard = this.stripeElements.create('card');
            this.stripeCard.mount('#stripe-card-element');
        }

        loadPayPalForm(container) {
            container.html(`
                <div class="paypal-info">
                    <p>You will be redirected to PayPal to complete your payment.</p>
                    <div id="paypal-button-container"></div>
                </div>
            `);

            // Initialize PayPal button if SDK is loaded
            if (typeof paypal !== 'undefined') {
                this.initPayPalButton();
            }
        }

        loadApplePayForm(container) {
            container.html(`
                <div class="apple-pay-info">
                    <p>Click "Pay Now" to proceed with Apple Pay</p>
                </div>
            `);
        }

        loadGooglePayForm(container) {
            container.html(`
                <div class="google-pay-info">
                    <p>Click "Pay Now" to proceed with Google Pay</p>
                </div>
            `);
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

                // Collect method-specific data
                if (this.currentMethod === 'stripe') {
                    const stripeResult = await this.processStripePayment();
                    if (!stripeResult.success) {
                        throw new Error(stripeResult.error);
                    }
                    paymentData = {...paymentData, ...stripeResult.data};
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

        async processStripePayment() {
            if (!this.stripe || !this.stripeCard) {
                return {success: false, error: 'Stripe is not initialized'};
            }

            const {token, error} = await this.stripe.createToken(this.stripeCard);
            
            if (error) {
                return {success: false, error: error.message};
            }

            return {
                success: true,
                data: {
                    stripe_token: token.id
                }
            };
        }

        initPayPalButton() {
            // PayPal button initialization would go here
            // This requires the PayPal SDK to be loaded
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
        }
    }

    PaymentWidget.DEFAULTS = {
        orderId: null,
        amount: 0,
        currency: 'USD',
        enabledMethods: ['stripe', 'paypal', 'manual_check'],
        stripePublicKey: null,
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
