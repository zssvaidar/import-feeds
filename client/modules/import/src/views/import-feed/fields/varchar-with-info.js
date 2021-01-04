

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

Espo.define('import:views/import-feed/fields/varchar-with-info', 'views/fields/varchar',
    Dep => Dep.extend({

        detailTemplate: 'import:import-feed/fields/varchar-with-info/detail',

        listTemplate: 'import:import-feed/fields/varchar-with-info/detail',

        data() {
            let data = Dep.prototype.data.call(this);
            data.isRequired = !!this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'required']);
            data.extraInfo = this.getExtraInfo();
            return data;
        },

        getExtraInfo() {
            let extraInfo = null;

            let fieldName = (this.model.get('customData') || {}).fieldName;
            if (this.model.get('isLink') || this.model.get('isLinkMultiple') && fieldName) {
                extraInfo = `
                    <span class="text-muted small">
                        ${this.translate('importBy', 'labels', 'ImportFeed')}: ${fieldName}
                    </span>`;
            }

            if (this.model.get('attributeId')) {
                extraInfo = `
                    <span class="text-muted small">
                        ${this.translate('Attribute', 'scopeNames', 'Global')}
                    </span>`;
            }

            if (this.model.get('scope')) {
                extraInfo += `
                    <br>
                    <span class="text-muted small">
                        ${this.translate('scope', 'fields')}: ${this.model.get('scope')}
                    </span>`;
            }

            return extraInfo;
        },

        getValueForDisplay() {
            let name = this.translate(this.model.get(this.name), 'fields', this.model.get('entity'));
            if (this.model.get('attributeId') && this.model.get('locale')) {
                name += ' > ' + this.model.get('locale');
            }

            return name;
        }

    })
);