document.observe('dom:loaded', function() {
    if (typeof OnecolumnCheckout !== 'undefined') {
        OnecolumnCheckout.saveStep = OnecolumnCheckout.saveStep.wrap(function(o, url, button, callback, params) {
            if (url !== checkout.urls.payment_method ||
                payment.getCurrentMethod() != 'cryozonic_stripe' ||
                typeof createStripeToken === 'undefined' ||
                typeof Stripe === 'undefined') {

                return o(url, button, callback, params);
            }

            createStripeToken(function(err) {
                if (err) {
                    cryozonic.displayCardError(err, true);
                } else {
                    return o(url, button, callback, params);
                }
            });
        });
    }
});
