document.observe('firecheckout:saveBefore', function (e) {
    if (payment.getCurrentMethod() !== 'networkmerchant') {
        return;
    }

    e.memo.params['fc-dry-run'] = true;
});

document.observe('firecheckout:setResponseAfter', function(e) {
    var response = e.memo.response;
    if (!response.method || response.method !== 'networkmerchant') {
        return;
    }
    checkout.setLoadWaiting(true);
    networkmerchantSolupaySave(); // see app/design/frontend/base/default/template/networkmerchant/form/payment.phtml
});
