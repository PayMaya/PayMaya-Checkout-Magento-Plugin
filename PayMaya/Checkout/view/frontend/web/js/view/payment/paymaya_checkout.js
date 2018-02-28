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
                type: 'paymaya_checkout',
                component: 'PayMaya_Checkout/js/view/payment/method-renderer/paymaya_checkout'
            }
        );

        return Component.extend({});
    }
);