/**
 * Functionality specific for the Magento default OnePageCheckout
 */
;billink.subModules.push(new function()
{
    var self = this;

    self.formController = null;

    /**
     * Called when the page is loaded
     */
    self.onPageLoad = function()
    {
        self.formController = billink.paymentFormController;

        if(!billink.isAlternateDeliveryAddressAllowed)
        {
            var billingUseForShippingYesElem = $('billing:use_for_shipping_yes');
            var billingUseForShippingNoElem = $('billing:use_for_shipping_no');

            if (billingUseForShippingYesElem && billingUseForShippingNoElem) {
                billingUseForShippingNoElem.observe('change', function () {
                    addDifferentAddressMessage();
                });
                billingUseForShippingYesElem.observe('change', function () {
                    addDifferentAddressMessage();
                });
            }

            addDifferentAddressMessage();
        }
    };


    /**
     * Called when the form is loaded
     */
    self.onFormLoad = function() {

        //Update address fields
        self.formController.updateBillingAddressFields("");
        self.formController.updateShippingAddressFields("");
    };


    /**
     * Add a message letting the user know that Billink cannot be used with two different addresses
     */
    var addDifferentAddressMessage = function()
    {
        //Check if the invoice and shipping address are the same
        //Check both checkboxes, because OSC only shows one of them
        var isSendToSameAddress = true;
        var checkboxNo = $('billing:use_for_shipping_no');
        var checkboxYes = $('billing:use_for_shipping_yes');
        var visibleCheckbox;

        if(checkboxNo)
        {
            visibleCheckbox = checkboxNo;
            isSendToSameAddress = !checkboxNo.checked
        }
        else if(checkboxYes)
        {
            visibleCheckbox = checkboxYes;
            isSendToSameAddress = checkboxYes.checked
        }

        //Remove old text
        var oldMessage = $(billink.checkoutPageController.differentAddressMessageElementId );
        if(oldMessage)
        {
            oldMessage.remove();
        }

        //Add text
        if(billink.isReadyToUse && !isSendToSameAddress)
        {
            var label = visibleCheckbox.next();
            if(label)
            {
                $(label).insert({
                    after: billink.checkoutPageController.getDifferentAddressMessage()
                });
            }
        }
    };
});