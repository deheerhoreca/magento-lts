$(document.body).observe('click', function (e) {
    var el = e.findElement('.vendor-rates');

    if (!el) {
        return;
    }

    document.fire('firecheckout:shippingMethod:clickBefore', {
        el: el
    });

    var sections = FC.Ajax.getSectionsToUpdate('shipping-method');
    if (sections.length) {
        checkout.update(
            checkout.urls.shipping_method,
            FC.Ajax.arrayToJson(sections)
        );
    }

    if (typeof deliveryDate == 'object') {
        deliveryDate.toggleDisplay(el.value);
    }
});
