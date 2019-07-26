/**
 * Fix for asterisk element near field label and placeholder
 * @see components/form-compact.js
 */
ZipUpdater.prototype.update = ZipUpdater.prototype.update.wrap(function (o) {
    o();

    if (!this.zipElement || !this.zipElement.id) {
        return;
    }

    var label = $$('label[for="' + this.zipElement.id + '"]')[0],
        isRequired;
    if (label) {
        if (this.zipElement.hasClassName('required-entry')) {
            label.addClassName('required');
            isRequired = true;
        } else {
            label.removeClassName('required');
            isRequired = false;
        }
    }

    document.fire('firecheckout:zipUpdaterUpdateAfter', {
        el: this.zipElement,
        label: label,
        isRequired: isRequired
    });
});
