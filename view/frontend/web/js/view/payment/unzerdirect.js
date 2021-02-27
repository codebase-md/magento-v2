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
                type: 'unzerdirect_mobilepay',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/mobilepay'
            },
            {
                type: 'unzerdirect_vipps',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/vipps'
            },
            {
                type: 'unzerdirect_paypal',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/paypal'
            },
            {
                type: 'unzerdirect_viabill',
                component: 'UnzerDirect_Gateway/js/view/payment/method-renderer/viabill'
            }
        );

        return Component.extend({});
    }
);