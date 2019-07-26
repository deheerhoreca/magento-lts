document.observe('firecheckout:saveBefore', function (e) {
    if (e.memo.forceSave) {
        return;
    }

    if (payment.getCurrentMethod().indexOf("klarna_payments_") === 0) {
        e.memo.stopFurtherProcessing = true;
        payment.save();
    }
});

document.observe('firecheckout:updateAfter', function (e) {
    if (e.memo.url === checkout.urls.billing_address) {
        return checkout.updateSections('payment-method');
    }

    if (e.memo.url === checkout.urls.update_sections) {
        var updatedSections = e.memo.parameters['sections[]'];
        if (updatedSections && updatedSections.indexOf('payment-method') > -1) {
            klarnaPaymentArray = [];
        }
    }
});
