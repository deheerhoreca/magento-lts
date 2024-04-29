document.observe('dom:loaded', function () {
    // use 'tel' to prevent spinners in input fields
    new FC.FormFieldManager({
        '#billing\\:month': {
            type: 'tel',
            inputmode: 'numeric'
        },
        '#billing\\:day': {
            type: 'tel',
            inputmode: 'numeric'
        },
        '#billing\\:year': {
            type: 'tel',
            inputmode: 'numeric'
        }
    });
});

var firecheckoutCcFieldmanager = new FC.FormFieldManager({
    '#checkout-payment-method-load input[name="payment[cc_number]"': {
        type: 'tel',
        inputmode: 'numeric',
        autocomplete: 'cc-number'
    },
    '#checkout-payment-method-load input[name="payment[cc_cid]"': {
        type: 'tel',
        inputmode: 'numeric',
        autocomplete: 'cc-csc'
    }
});
document.observe('firecheckout:paymentMethod:afterInitAfter', function() {
    firecheckoutCcFieldmanager.processRules();
});
