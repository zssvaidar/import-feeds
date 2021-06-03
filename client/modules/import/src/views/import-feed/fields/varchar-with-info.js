/*
 * Import Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
                extraInfo = `<span class="text-muted small">${this.translate('importBy', 'labels', 'ImportFeed')}: ${fieldName}</span>`;
                if (this.model.get('createIfNotExist')) {
                    extraInfo += `<br><span class="text-muted small">${this.translate('createIfNotExist', 'fields', 'ImportFeed')}</span>`;
                }
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