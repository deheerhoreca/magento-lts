function updateReview() {
    checkout.updateSections('review');
}

document.observe('dom:loaded', function () {
    $$('.amgiftwrap-cart-button').each(function (el) {
        $('shipping-method').down('.block-content').insert({
            bottom: el
        });
    });
});
