if (typeof mypajQuery !== 'undefined') {
    mypajQuery(document).on('change', 'input[name="shipping_method"]', function () {
        var sections = FC.Ajax.getSectionsToUpdate('shipping-method');
        if (sections.length) {
            checkout.update.bind(checkout).delay(
                0.1, // give some time to modify hidden input values by third-party modules
                checkout.urls.shipping_method,
                FC.Ajax.arrayToJson(sections)
            );
        }
    });
}
