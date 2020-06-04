jQuery.noConflict();

function discresm2(gb) {
  var currentVal = parseInt(jQuery(gb).next().val());
  if (!isNaN(currentVal) && currentVal > 1) {
    newVal = currentVal - 1;
    jQuery(gb).next().val(newVal);
    setTimeout(function() {
      jQuery(gb).next().next().next().trigger('click');
    }, 250);
  } else if (currentVal == 1) {
  } else {
    jQuery(gb).next().val(0);
    setTimeout(function() {
      jQuery(gb).next().next().next().trigger('click');
    }, 250);
  }
}

function increasem2(gb) {
  var currentVal = parseInt(jQuery(gb).prev().val());
  if (!isNaN(currentVal)) {
    jQuery(gb).prev().val(currentVal + 1);
    setTimeout(function() {
      jQuery(gb).next().trigger('click');
    }, 250);
  } else {
    jQuery(gb).prev().val(0);
    jQuery(gb).next().trigger('click');
  }
}

$j(document).ready(function() {

  var doc = document.documentElement;
  var w = window;

  var prevScroll = w.scrollY || doc.scrollTop;
  var curScroll;
  var direction = 0;
  var prevDirection = 0;

  var header = document.getElementById('header');

  var checkScroll = function() {

    /*
    ** Find the direction of scroll
    ** 0 - initial, 1 - up, 2 - down
    */

    curScroll = w.scrollY || doc.scrollTop;
    if (curScroll > prevScroll) { 
      //scrolled up
      direction = 2;
    }
    else if (curScroll < prevScroll) { 
      //scrolled down
      direction = 1;
    }

    if (direction !== prevDirection) {
      toggleHeader(direction, curScroll);
    }
    
    prevScroll = curScroll;
  };

  var toggleHeader = function(direction, curScroll) {
    if (direction === 2 && curScroll > 150) { 
      
      //replace 52 with the height of your header in px

      header.classList.add('hide');
      prevDirection = direction;
    }
    else if (direction === 1) {
      header.classList.remove('hide');
      prevDirection = direction;
    }
  };
  
  window.addEventListener('scroll', checkScroll, { passive: true });

});
