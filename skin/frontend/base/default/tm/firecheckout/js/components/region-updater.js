/**
 * Fix for asterisk element near field placeholder
 * @see components/form-compact.js
 */
RegionUpdater.prototype.update = RegionUpdater.prototype.update.wrap(function (o) {
    o();

    var el,
        label,
        isRequired;

    if (this.regions[this.countryEl.value]) {
        el = this.regionSelectEl;
    } else {
        el = this.regionTextEl;
    }

    isRequired = el.hasClassName('required-entry');
    label = $$('label[for="' + el.id + '"]')[0];

    document.fire('firecheckout:regionUpdaterUpdateAfter', {
        el: el,
        label: label,
        isRequired: isRequired
    });
});
