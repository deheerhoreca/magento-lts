AWSearchautocomplete.prototype.onAutocompleterStartIndicator =  function() {
    $$('button.search-button').first().setStyle({
        display: 'none'
    });
    this.targetElement.setStyle({
        backgroundImage: 'url("' + this.indicatorImage + '")',
        backgroundRepeat: 'no-repeat',
        backgroundPosition: 'right'
    });
}

AWSearchautocomplete.prototype.onAutocompleterStopIndicator = function() {
    this.targetElement.setStyle({
        backgroundImage: 'none'
    });
    $$('button.search-button').first().setStyle({
        display: 'inline-block'
    });
}

enquire.register('(max-width: ' + bp.medium + 'px)', {
    match: function () {
        jQuery('#myContainer').hide();
    },
    unmatch: function () {
        jQuery('#myContainer').hide();
    }
});