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

document.observe("dom:loaded", bodyOnload);

$("eol").on('change', function() {
  bodyOnload();
});

function bodyOnload() {

  var eol = $('eol').getValue();
  
  if (eol == 2075) {
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

}
