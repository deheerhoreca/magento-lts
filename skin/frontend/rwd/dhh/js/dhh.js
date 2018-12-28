jQuery.noConflict();
jQuery(document).ready(function() {
  jQuery("#content-slider").lightSlider({
    loop: true,
    auto: true,
    speed: 1000,
    item: 1,
    keyPress: true,
    mode: 'fade',
    pause: 4000
  });

  jQuery(".nav-primary").hover(function() {
    jQuery('#darkness').delay(300).fadeTo(0, 1);
  }, function() {
    jQuery('#darkness').fadeTo(0, 0, function() {
      jQuery('#darkness').hide();
    });
  });

  jQuery(".skip-link.skip-cart").click(function() {
    jQuery('#darknessx').delay(0).fadeTo(0, 1);
  });

  jQuery('#billing\\:firstname, #billing\\:lastname, #billing\\:city').keydown(function(event) {
    if (this.selectionStart == 0 && event.keyCode >= 65 && event.keyCode <= 90 && !(event.shiftKey) && !(event.ctrlKey) && !(event.metaKey) && !(event.altKey)) {
      var $t = jQuery(this);
      event.preventDefault();
      var char = String.fromCharCode(event.keyCode);
      $t.val(char + $t.val().slice(this.selectionEnd));
      this.setSelectionRange(1, 1);
    }
  });
});

jQuery(function() {
  jQuery(window).scroll(function() {
    if (jQuery(window).width() > 680) {
      if (jQuery(this).scrollTop() > 0) {
        jQuery('#header-nav').addClass('headmv');
        jQuery('.page-header-container.grid-old').addClass('scrlng');
      } else {
        jQuery('#header-nav').removeClass('headmv');
        jQuery('.page-header-container.grid-old').removeClass('scrlng');
      }
    }
  });
});

function hidedark() {
  jQuery('#darknessx').fadeTo(0, 0, function() {
    jQuery('#darknessx').hide();
  });
}

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

/*
jQuery( ".nav-primary" ).mouseenter(function() {
  setTimeout(function(){
   jQuery('#darkness').fadeTo(200, 1);
   }, 200);

  }).mouseleave(function() {

      jQuery('#darkness').fadeTo(0, 0, function(){
       jQuery('#darkness').hide();
    });

  });
*/
