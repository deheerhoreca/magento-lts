document.observe('dom:loaded', function(e) {
    if ($('ppplus')) {
        // ppp iframe is already shown
        return;
    }

    // try to reload payment section to render ppp iframe
    checkout.updateSections(['payment-method']);
});

// Update payment methods section
document.observe('firecheckout:setResponseAfter', function(e) {
    var response = e.memo.response;
    if (!response.update_section || e.memo.url === checkout.urls.update_sections) {
        return;
    }

    if ($('ppplus')) {
        // ppp iframe is already shown
        return;
    }

    // try to reload payment section to render ppp iframe
    checkout.updateSections(['payment-method']);
});

// Place order
document.observe('firecheckout:setResponseAfter', function(e) {
    var response = e.memo.response;
    if (!response.method || response.method !== 'iways_paypalplus_payment') {
        return;
    }

    var url = checkout.urls.billing_address.replace(
        'firecheckout/index/saveBilling',
        'paypalplus/index/validate'
    );
    checkout.update(url, { validate: true }, function (transport) {
        transport.stopFurtherProcessing = true;

        // code below copied from paypalplus/review/button.phtml
        try {
            response = transport.responseText.evalJSON();
        } catch (e) {
            response = {};
        }

        if (response.redirect) {
            review.isSuccess = true;
            window.ppp.doCheckout();
            return;
        }

        if (response.success) {
            review.isSuccess = true;
            window.ppp.doCheckout();
        } else {
            var msg = response.error_messages;
            if (typeof(msg) == 'object') {
                msg = msg.join("\n");
            }
            if (msg) {
                alert(msg);
            }
        }
    });
});
