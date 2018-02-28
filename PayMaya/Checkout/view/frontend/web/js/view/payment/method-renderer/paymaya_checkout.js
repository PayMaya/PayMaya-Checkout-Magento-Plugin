define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/redirect-on-success',
    'mage/url'
], function (Component, redirectOnSuccessAction, urlBuilder) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'PayMaya_Checkout/payment/form'
        },

        getCode: function () {
            return 'paymaya_checkout';
        },

        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },

        afterPlaceOrder: function () {
            this.redirectAfterPlaceOrder = true;
            redirectOnSuccessAction.redirectUrl = urlBuilder.build('paymaya/checkout/start');
        }
    });
});