/**
 * Store the billink payment form to session on change
 */
;billink.paymentFormSession = new function()
{
    var self = this;
    self.setPaymentFormFieldsUrl = '';
    self.getPaymentFormFieldsUrl = '';
    self.formController = null;

    /**
     * Attach listeners
     */
    self.init = function(setPaymentFormFieldsUrl, getPaymentFormFieldsUrl, formController)
    {
        self.setPaymentFormFieldsUrl = setPaymentFormFieldsUrl;
        self.getPaymentFormFieldsUrl = getPaymentFormFieldsUrl;
        self.formController = formController;

        // if the form changes, post it to server for session storage
        $$("#payment_form_billink input").invoke('observe', 'change', function() {
            self.savePaymentFormFieldsInSession();
        });
    };

    /**
     * Save fields in session
     */
    self.savePaymentFormFieldsInSession = function(){
        var values = {};

        //grab text fields
        $$("#payment_form_billink input").each(function(element) {
            values[element.name] = element.getValue();
        });

        //Grab dropdowns
        $$("#payment_form_billink select").each(function(element) {
            values[element.name] = element.getValue();
        });

        //grab selected radio fields
        $$("#payment_form_billink input:checked").each(function(element) {
            values[element.name] = element.getValue();
        });

        new Ajax.Request(self.setPaymentFormFieldsUrl, {
            method : 'post',
            parameters : values
        });
    };
};