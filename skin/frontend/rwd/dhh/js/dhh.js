jQuery.noConflict();
jQuery(document).ready(function() {
  jQuery("#top-nav").hover(function() {
    jQuery('#darkness').fadeTo(0, 1);
  }, function() {
    jQuery('#darkness').fadeTo(0, 0, function() {
      jQuery('#darkness').hide();
    });
  });
});

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

function discresm(gb) {
  // Get its current value
  var currentVal = parseInt(jQuery(gb).next().val());

  nb = jQuery(gb).next().attr('data-item-id');

  // If it isn't undefined or its greater than 0
  if (!isNaN(currentVal) && currentVal > 0) {
    // Decrement one
    jQuery(gb).next().val(currentVal - 1);
    setTimeout(function() {
      jQuery('#qbutton-' + nb + '').trigger('click');
    }, 400);
  } else {
    // Otherwise put a 0 there
    jQuery(gb).next().val(0);
    setTimeout(function() {
      jQuery('#qbutton-' + nb + '').trigger('click');
    }, 400);
  }
}

function increasem(gb) {
  // Get its current value
  var currentVal = parseInt(jQuery(gb).prev().val());
  nb = jQuery(gb).prev().attr('data-item-id');
  // If is not undefined
  if (!isNaN(currentVal)) {
    // Increment
    jQuery(gb).prev().val(currentVal + 1);
    setTimeout(function() {
      jQuery('#qbutton-' + nb + '').trigger('click');
    }, 400);

  } else {
    // Otherwise put a 0 there
    jQuery(gb).prev().val(0);
    setTimeout(function() {
      jQuery('#qbutton-' + nb + '').trigger('click');
    }, 400);
  }
}

function discresm2(gb) {
  // Get its current value
  var currentVal = parseInt(jQuery(gb).next().val());
  // If it isn't undefined or its greater than 0
  if (!isNaN(currentVal) && currentVal > 0) {
    // Decrement one
    jQuery(gb).next().val(currentVal - 1);
    setTimeout(function() {
      jQuery(gb).next().next().next().trigger('click');
    }, 400);
  } else {
    // Otherwise put a 0 there
    jQuery(gb).next().val(0);
    setTimeout(function() {
      jQuery(gb).next().next().next().trigger('click');
    }, 400);
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
    }, 400);

  } else {
    // Otherwise put a 0 there
    jQuery(gb).prev().val(0);
    jQuery(gb).next().trigger('click');
  }
}
