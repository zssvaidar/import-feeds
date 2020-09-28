

Espo.define('import:views/import-feed/fields/enum-with-custom-tooltip', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        init() {
            Dep.prototype.init.call(this);

            if (this.mode === 'detail' || this.mode === 'edit') {
                this.once('after:render',() => {
                    this.setupCustomTooltip();
                });
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:type', () => {
                this.setupCustomTooltip();
            });
        },

        setupCustomTooltip() {
            let lastTooltip = this.getLabelElement().find('a.text-muted');
            if (lastTooltip) {
                lastTooltip.popover('destroy');
                lastTooltip.remove();
            }

            let $a = $('<a href="javascript:" class="text-muted"><span class="fas fa-info-circle"></span></a>');
            let $label = this.getLabelElement();
            $label.append(' ');
            this.getLabelElement().append($a);
            $a.popover({
                placement: 'bottom',
                container: 'body',
                html: true,
                content: this.translate(this.model.get('type'), 'types', this.model.name).replace(/\n/g, "<br />"),
                trigger: 'click',
            }).on('shown.bs.popover', function () {
                $('body').one('click', function () {
                    $a.popover('hide');
                });
            });
            this.on('remove', function () {
                if ($a) {
                    $a.popover('destroy');
                }
            }, this);
        }

    })

});