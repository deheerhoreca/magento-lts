FC.WebkulWkbraintree = {
    counter: 0,

    placeOrder: function () {
        if ($('wkpayment_method_nonce').getValue()) {
            checkout.setLoadWaiting(false);
            checkout.setLoadingButton($$('button.btn-checkout')[0], false);

            return checkout.save('', true);
        }

        // don't wait too long in case of failed validation on braintree side
        if (FC.WebkulWkbraintree.counter++ > 12) {
            checkout.setLoadWaiting(false);
            checkout.setLoadingButton($$('button.btn-checkout')[0], false);

            return;
        }

        return setTimeout(FC.WebkulWkbraintree.placeOrder, 200);
    }
};


document.observe('dom:loaded', function () {
    if ($('wkpayment_method_nonce')) {
        $('wkpayment_method_nonce').remove();
    }

    $('firecheckout-form').insert({
        top: "<input type='hidden' name='payment[payment_method_nonce]' id='wkpayment_method_nonce' value=''/>"
    });
});

document.observe('firecheckout:saveBefore', function (e) {
    if (payment.getCurrentMethod() !== 'wkbraintree') {
        return;
    }

    if (e.memo.forceSave && $('wkpayment_method_nonce').getValue()) {
        return;
    }

    e.memo.stopFurtherProcessing = true;

    $('wkpayment_method_nonce').setValue('');
    document.getElementById('wk_trigger_btn').click();

    checkout.setLoadWaiting(true);
    checkout.setLoadingButton($$('button.btn-checkout')[0]);

    FC.WebkulWkbraintree.counter = 0;
    FC.WebkulWkbraintree.placeOrder();
});
