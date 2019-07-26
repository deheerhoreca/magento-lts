function firecheckoutSyncPookCollectInStore() {
    $('collectinstore-search').hide();
    $$('#collectinstore-search input[name="shipping_method"]').invoke('remove');
    $$('#checkout-shipping-method-load > input[name="shipping_method"]').invoke('remove');

    if (shippingMethod.getCurrentMethod() === 'collectinstore_collectinstore') {
        $('collectinstore-search').show();
    }
}

document.observe('dom:loaded', function() {
    firecheckoutSyncPookCollectInStore();
});

document.observe('firecheckout:setResponseAfter', function(e) {
    firecheckoutSyncPookCollectInStore();
});
