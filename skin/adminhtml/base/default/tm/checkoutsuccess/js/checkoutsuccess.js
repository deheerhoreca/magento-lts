(function(){

    var _templateParent = new Template(
        '<ul class="chosen-choices">#{inner}</ul>'
    );

    var _templateChild = new Template(
        '<li class="search-choice">'
         + '<span class="name">#{id}</span>'
         + '<span class="value">#{title}</span>'
         + '<a class="search-choice-close" title="Remove block"></a>'
         +'</li>'
    );

    MockupSuccessPage = Class.create();
    MockupSuccessPage.prototype = {

        initialize: function(){

            this.availableBlocksSection = '#row_checkoutsuccess_mockup_available_blocks .value';

            this.containerCssClass = 'chosen-container chosen-container-multi checkoutsuccess-mockup';
            this.boardCssClass = 'checkoutsuccess-mockup-board';

            this.drake = dragula(
                {
                    copy: function (el, source) {
                        return source.hasClassName('copy-only');
                    },
                    accepts: function (el, target) {
                        return !target.hasClassName('copy-only');
                    }
                }
            );
            this.drake.on('dragend', this.updateValues.bind(this));

            this.availableBlocks = [];

            var self = this;

            $$(this.availableBlocksSection).each(
                function(section){
                    var select = section.down('select'),
                        values = [];
                    self.availableBlocks = [];

                    for (var i = 0; i < select.length; i++) {
                        self.availableBlocks[select.options[i].value] = select.options[i].text;
                        values.push(select.options[i].value);
                    }

                    var mockupContainer = self.createMockupContainer(values);
                    mockupContainer.down('ul').addClassName('copy-only');
                    section.next().update();
                    section.next(1).update();
                    select.insert({after: mockupContainer});
                    select.hide();

                    self.drake.containers.push(
                        mockupContainer.down('ul'),
                        {copy: true}
                    );
                }
            );

            $$('#row_checkoutsuccess_mockup_board .value').each(
                function (placeholder) {
                    placeholder.setStyle({minWidth: '500px'});
                    var textarea = placeholder.down('textarea'),
                        board = new Element(
                            'div',
                            {
                                'class': self.boardCssClass,
                                'data-related': textarea.id
                            }
                        );
                    var data = textarea.value ? JSON.parse(textarea.value) : {};
                    var containerNames = ['top', 'middleleft', 'middleright', 'bottom'];
                    var i = 0;

                    for (i = 0; i < containerNames.length; i++) {
                        var container = self.createMockupContainer(data[containerNames[i]], containerNames[i]);
                        board.insert(container);
                        if (!textarea.disabled) {
                            self.drake.containers.push(container.down('ul'));
                        }
                    }

                    if (textarea.disabled) {
                        board.addClassName('disabled');
                    }

                    textarea.insert({after: board});
                    textarea.hide();

                    placeholder.observe(
                        'click',
                        function (e){
                            var clickedEl = Event.element(e);
                            if (clickedEl.hasClassName('search-choice-close')) {
                                var child = clickedEl.up('.search-choice');
                                if (child) {
                                    var disabledParent = child.up('.disabled');
                                    if (!disabledParent) {
                                        child.remove();
                                        self.updateValues();
                                    }
                                }
                            }
                        }
                    );
                }
            );

        },

        createMockupContainer: function(values, addClass) {
            var blocks = !values ? {} : values,
                innerHtml = '';
            for (var i = 0; i < blocks.length; i++) {
                if (blocks[i]) {
                    innerHtml +=_templateChild.evaluate(
                        {
                            'id': blocks[i],
                            'title': this.availableBlocks[blocks[i]]
                        }
                    );
                }
            };

            var div = new Element('div', { 'class': this.containerCssClass });
            if (addClass) {
                div.addClassName(addClass);
            };

            div.insert(_templateParent.evaluate({'inner':innerHtml}));
            return div;
        },

        updateValues: function() {
            var newValue = {},
                self = this,
                cssSelector = '.' + this.containerCssClass.split(' ').join('.');
            $$(cssSelector).each(
                function (element){
                    if (element.down('ul').hasClassName('copy-only')) {
                        return;
                    }

                    var key = element.getAttribute('class')
                        .replace(self.containerCssClass, '')
                        .split(' ').join('');
                    newValue[key] = [];
                    element.select('.search-choice').map(
                        function(obj, i){
                            newValue[key].push(obj.down('.name').innerHTML);
                        }
                    );
                }
            );
            $$('.' + this.boardCssClass).each(
                function (element){
                    $(element.getAttribute('data-related')).value =
                        JSON.stringify(newValue);
                }
            );
        },

        toggleSection: function(container){
            var board = container.down('.' + this.boardCssClass),
                self = this;
            if (!board) {
                return;
            }

            if (board.hasClassName('disabled')) {
                board.removeClassName('disabled');
                var cssSelector = '.' + this.containerCssClass.split(' ').join('.');
                board.select(cssSelector).each(
                    function (container){
                        self.drake.containers.push(container.down('ul'));
                    }
                );
            } else {
                board.addClassName('disabled');
                var cssSelector = '.' + this.containerCssClass.split(' ').join('.');
                board.select(cssSelector).each(
                    function (container){
                        var index = self.drake.containers.indexOf(container.down('ul'));
                        if (index >= 0) {
                            self.drake.containers.splice(index, 1);
                        }
                    }
                );
            }
        }

    }

})();

document.observe(
    "dom:loaded",
    function(){

        window.mockupSP = new MockupSuccessPage();
        toggleValueElements = toggleValueElements.wrap(
            function (callOriginal, checkbox, container, excludedElements, checked){
                mockupSP.toggleSection(container);
                return callOriginal(checkbox, container, excludedElements, checked);
            }
        );
    }
);
