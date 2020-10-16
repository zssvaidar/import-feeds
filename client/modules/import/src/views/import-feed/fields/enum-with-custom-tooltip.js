

/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

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