define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'unzerdirect_gateway',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/unzerdirect'
            },
            {
                type: 'unzerdirect_klarna',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/klarna'
            },
            {
                type: 'unzerdirect_paypal',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/paypal'
            }
        );

        return Component.extend({});
    }
);