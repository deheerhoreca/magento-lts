document.observe('firecheckout:paymentMethod:addObserversAfter', function () {
    $$('input[name="payment[grinet_payu_taksit_payu]"]').each(function (el) {
        el.observe('click', function() {
            var sections = FC.Ajax.getSectionsToUpdate('payment-method');
            if (!sections.length) {
                return;
            }

            checkout.update(
                checkout.urls.payment_method,
                FC.Ajax.arrayToJson(sections)
            );
        });
    });
});
