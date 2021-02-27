define(
    [
        'Magento_Checkout/js/view/payment/default',
        'UnzerDirect_Gateway/js/action/redirect-on-success'
    ],
    function (Component, unzerDirectRedirect) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'UnzerDirect_Gateway/payment/form',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,

            /**
             * @return {exports}
             */
            initObservable: function () {
                this._super()
                    .observe('paymentReady');

                return this;
            },

            /**
             * @return {*}
             */
            isPaymentReady: function () {
                return this.paymentReady();
            },

            getCode: function() {
                return 'unzerdirect_klarna';
            },
            getData: function() {
                return {
                    'method': this.item.method,
                };
            },
            afterPlaceOrder: function() {
                unzerDirectRedirect.execute();
            },
            getPaymentLogo: function () {
                return window.checkoutConfig.payment.unzerdirect_klarna.paymentLogo;
            },
        });
    }
);