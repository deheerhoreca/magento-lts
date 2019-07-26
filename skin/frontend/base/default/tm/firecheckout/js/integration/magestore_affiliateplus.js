document.observe('affiliateplus:updateSuccess', function(e) {
    checkout.update(checkout.urls.shopping_cart);
});
