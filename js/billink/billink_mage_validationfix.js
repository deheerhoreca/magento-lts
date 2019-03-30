//Overwrite function
;Validation.ajaxError = function(elm, errorMsg) {

    //The var "name" has been overwritten
    //Normally Magento can only show 1 error for a field. If you want to show another error on the same field, it just
    //shows the first one.
    //
    //For example: we have the field "billink_payment_method", and we want to show the error "incorrect address"
    //because the user inserted an inccorect address. This would work just fine. Went the customer fixes the address
    //and tries again, we want to show the error "incorrect phone number". This would not work, becuase Magento would
    //just show the message "incorrect address" again.
    //
    //To counter this, we make the 'name' unique per error. This forces Magento to refresh the error for a field.
    var name = elm.name + errorMsg;

    var advice = Validation.getAdvice(name, elm);
    if (advice == null) {
        advice = this.createAdvice(name, elm, false, errorMsg);
    }

    //The line below has also been updated to pass the name, instead of a fixed string
    this.showAdvice(elm, advice, name);

    this.updateCallback(elm, 'failed');
    elm.addClassName('validation-failed');
    elm.addClassName('validate-ajax');
    if (Validation.defaultOptions.addClassNameToContainer && Validation.defaultOptions.containerClassName != '') {
        var container = elm.up(Validation.defaultOptions.containerClassName);
        if (container && this.allowContainerClassName(elm)) {
            container.removeClassName('validation-passed');
            container.addClassName('validation-error');
        }
    }
};


