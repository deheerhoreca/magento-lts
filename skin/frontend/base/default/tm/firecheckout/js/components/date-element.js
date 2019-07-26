/**
 * Add jscalendar support for the DateElements
 */
Varien.DateElement.prototype.initialize = Varien.DateElement.prototype.initialize.wrap(
    function(o, type, content, required, format) {
        o(type, content, required, format);

        if (!type || -1 === format.indexOf('%')) {
            // 1. DateElement can be created without parameters
            //    @see Varien.DateElement in js/varien/js.js
            // 2. Do not create calendar, if format is invalid (third-party modules bugs)
            return;
        }

        // dummy input for calendar. It will not be sent to the server.
        var label = this.full.up('.input-box').previous('label'),
            placeholder = label.innerHTML.replace(/<em.+?>*<\/em>/, '').stripTags(),
            input = new Element('input');

        input.addClassName('input-text')
            .writeAttribute('type', 'text')
            .writeAttribute('title', placeholder)
            .writeAttribute('placeholder', placeholder);
        input.validate = this.validate.bind(this);

        this.full.up().insert({
            after: input
        });

        this.day.up().hide();
        this.month.up().hide();
        this.year.up().hide();

        var self = this;
        FC.Calendar.create(input, {
            format: format,
            // listener for flatpickr.js
            onChange: function(selectedDates, dateStr, instance) {
                var date = selectedDates[0];
                self.day.setValue(date.getDate());
                self.month.setValue(date.getMonth() + 1);
                self.year.setValue(date.getFullYear());
                self.full.setValue(dateStr);
            },
            // listener for calendar.js fallback
            onSelect: function(calendar, dateStr) {
                var date = calendar.date;
                self.day.setValue(date.getDate());
                self.month.setValue(date.getMonth() + 1);
                self.year.setValue(date.getFullYear());
                self.full.setValue(dateStr);
                this.hide();
            }
        });
    }
);
