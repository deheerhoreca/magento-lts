if (Validation) {
  Validation.add('conditional-required', 'This is a required field.', function(v) {
    var pack_type = $('pack_type').getValue();
    if (pack_type != 0) {
      return ((v != null) && (v.length != 0));
    } else {
      return true;
    }

  });
}

document.observe("dom:loaded", dhh_change_required_fields);

function dhh_change_required_fields() {
  if ($('eol').getValue() == 2075) {
    // Double because the class exists twice...
    $('levertijd').removeClassName('required-entry');
    $('levertijd').removeClassName('required-entry');
    $('price_supplier_discount_perc').removeClassName('required-entry');
    $('price_supplier_discount_perc').removeClassName('required-entry');
    $('msrp').removeClassName('required-entry');
    $('msrp').removeClassName('required-entry');
  } else {
    $('levertijd').addClassName('required-entry');
    $('price_supplier_discount_perc').addClassName('required-entry');
    $('msrp').addClassName('required-entry');
  }
  
  // Add listener for changes
  $("eol").on('change', function() {
    dhh_change_required_fields();
  });
}
