var Zip_TM_Firecheckout = Class.create();
Zip_TM_Firecheckout.prototype = {
    initialize: function () {},
    setup: function (superClass) {
        _super = superClass;

        document.observe('firecheckout:setResponseAfter', function(e) {
            var response = e.memo.response;
            if (!response.method || response.method !== 'zipmoneypayment') {
                return;
            }
            checkout.setLoadWaiting(true);
            _super.checkout();
        });
    }
};

document.observe('dom:loaded', function() {
    if (typeof window.$zipCheckout !== 'undefined') {
        window.$zipCheckout.register(Zip_TM_Firecheckout, 'TM_Firecheckout');
    }
});
