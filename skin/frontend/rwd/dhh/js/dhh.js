jQuery.noConflict();

jQuery(function() {
  jQuery(window).scroll(function() {
    if (jQuery(window).width() > 680) {
      if (jQuery(this).scrollTop() > 0) {
        jQuery('#header-nav').addClass('headmv');
        jQuery('#page-header-container').addClass('scrlng');
        jQuery(".main-container").css("top", "58px");
        jQuery(".footer-wrapper").css("top", "58px");
      } else {
        jQuery('#header-nav').removeClass('headmv');
        jQuery('#page-header-container').removeClass('scrlng');
        jQuery(".main-container").css("top", "");
        jQuery(".footer-wrapper").css("top", "");
      }
    }
  });
});

function discresm2(gb) {
  // Get its current value
  var currentVal = parseInt(jQuery(gb).next().val());
  // If it isn't undefined or its greater than 1
  if (!isNaN(currentVal) && currentVal > 1) {
    // Decrement one
    newVal = currentVal - 1;
    jQuery(gb).next().val(newVal);
    setTimeout(function() {
      jQuery(gb).next().next().next().trigger('click');
    }, 250);
  } else if (currentVal == 1) {
    // Do nothing
  } else {
    // Otherwise put a 0 there
    jQuery(gb).next().val(0);
    setTimeout(function() {
      jQuery(gb).next().next().next().trigger('click');
    }, 250);
  }
}

function increasem2(gb) {
  // Get its current value
  var currentVal = parseInt(jQuery(gb).prev().val());
  // If is not undefined
  if (!isNaN(currentVal)) {
    // Increment
    jQuery(gb).prev().val(currentVal + 1);
    setTimeout(function() {
      jQuery(gb).next().trigger('click');
    }, 250);

  } else {
    // Otherwise put a 0 there
    jQuery(gb).prev().val(0);
    jQuery(gb).next().trigger('click');
  }
}
