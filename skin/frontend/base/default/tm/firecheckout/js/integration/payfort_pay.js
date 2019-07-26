document.observe('firecheckout:setResponseBefore', function (e) {
    if (e.memo.url !== checkout.urls.save || payment.getCurrentMethod() !== 'payfortcc') {
        return;
    }

    if (e.memo.response.redirect.indexOf('/payfort/') > -1) {
        // redirect integration type is enabled
        return;
    }

    delete e.memo.response.redirect;
    delete e.memo.response.success;

    var url = checkout.urls.billing_address.replace(
        'firecheckout/index/saveBilling',
        'payfort/payment/getMerchantPageData'
    );
    if ($('div-pf-iframe')) { // @see payfort's merchant-page.phtml
        payfortFortMerchantPage.submitMerchantPage(url);
    } else {
        payfortFortMerchantPage2.submitMerchantPage(payment.getCurrentMethod(), url);
    }
});
