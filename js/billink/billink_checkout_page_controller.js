/**
 * Controls page functions for checkout
 */
;billink.checkoutPageController = new function() {

    var self = this;

    /**
     * Id for the message element
     * @type {string}
     */
    self.differentAddressMessageElementId = 'billink-cannot-use-different-address-msg';

    /**
     * This function is called when the page is loaded
     */
    self.init = function()
    {
        //Send page loaded event to submodules
        for (var i = 0; i < billink.subModules.length; i++)
        {
            var subMod = billink.subModules[i];
            if (typeof subMod.onPageLoad === "function") {
                subMod.onPageLoad();
            }
        }
    };

    /**
     * Message to show when a user has selected different addresses (shipping and billing) when this is
     * not allowed in the Billink configuration
     * @returns {string}
     */
    self.getDifferentAddressMessage = function()
    {
        var message = Translator.translate('Let op: de Billink betaaloptie kan niet gebruikt worden als het verzendadres afwijkt van het factuur adres.');
        return '<p id="' + self.differentAddressMessageElementId + '" class="billink-billing-form-message notice-msg">' + message + '</p>'
    }
};