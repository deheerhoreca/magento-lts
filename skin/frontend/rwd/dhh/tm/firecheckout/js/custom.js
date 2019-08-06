document.observe('dom:loaded', function() {
  document.getElementById("agreement-1").checked = true;
});

document.observe('dom:loaded', function() {
    FC.DependentFields.addRule(
        'phone_to_country_billing',             // unique rule identifier
        {
            field: 'billing:company',           // field to watch
            value: '',                          // value to compare with field value, can be an array
            dependentField: 'billing:vat_id',   // dependent field, can be an array
            match: 'hidden',                    // field status, when field.value equals value
            unmatch: 'optional'                 // field status, when field.value not equals value
        }
    );
    FC.DependentFields.addRule(
        'phone_to_country_shipping',            // unique rule identifier
        {
            field: 'shipping:company',          // field to watch
            value: '',                          // value to compare with field value, can be an array
            dependentField: 'shipping:vat_id',  // dependent field, can be an array
            match: 'hidden',                    // field status, when field.value equals value
            unmatch: 'optional'                 // field status, when field.value not equals value
        }
    );
});

document.observe('dom:loaded', function() {
  document.getElementById("shipping-method").parentNode.style.display="none";
  document.getElementById("step-shipping-payment-method").classList.remove("col2-set");
  document.getElementById("step-shipping-payment-method").classList.add("col1-set");
});
