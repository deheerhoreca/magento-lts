addEventListener("DOMContentLoaded", function (event) {
    if (!window.Validation) {
        return;
    }
    Validation.addAllThese([
        ['validate-google-ads-conversion-id', 'Please enter the correct value, in a format: AW-123456, Code should contain 4-12 digits.', function(v) {
            return !v || /^AW-[0-9]{4,12}$/.test(v);
        }]
    ]);
});
