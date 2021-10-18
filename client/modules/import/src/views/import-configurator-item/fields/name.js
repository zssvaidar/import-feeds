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

Espo.define('import:views/import-configurator-item/fields/name', 'views/fields/enum',
    Dep => Dep.extend({

        listTemplate: 'import:import-configurator-item/fields/name/list',

        setup() {
            let entity = this.model.get('entity');
            let fields = this.getEntityFields(entity);

            this.params.options = [];
            this.translatedOptions = {};

            $.each(fields, field => {
                this.params.options.push(field);
                this.translatedOptions[field] = this.translate(field, 'fields', entity);
            });

            Dep.prototype.setup.call(this);
        },

        data() {
            let data = Dep.prototype.data.call(this);

            if (this.mode === 'list') {
                data.value = this.translate(data.value, 'fields', this.model.get('entity'));
                data.isRequired = !!this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'required']);
                data.extraInfo = this.getExtraInfo();
            }

            return data;
        },

        getPreparedValue(value) {
            if (this.model.get('type') === 'Field') {
                return this.translate(value, 'fields', this.model.get('entity'));
            }

            if (this.model.get('type') === 'Attribute') {
                return 'qwe 1';
            }

            return '';
        },

        getExtraInfo() {
            let extraInfo = null;

            if (this.model.get('importBy').length > 0) {
                const entityName = this.model.getLinkParam('default', 'entity');
                let translated = [];
                this.model.get('importBy').forEach(field => {
                    translated.push(this.translate(field, 'fields', entityName));
                });

                extraInfo = `<span class="text-muted small">${this.translate('importBy', 'fields', 'ImportConfiguratorItem')}: ${translated.join(', ')}</span>`;
                if (this.model.get('createIfNotExist')) {
                    extraInfo += `<br><span class="text-muted small">${this.translate('createIfNotExist', 'fields', 'ImportConfiguratorItem')}</span>`;
                }
            }

            if (this.model.get('type') === 'Attribute') {
                extraInfo = `<span class="text-muted small">${this.translate('Attribute', 'scopeNames', 'Global')}</span>`;
                extraInfo += `<br><span class="text-muted small">${this.translate('scope', 'fields')}: ${this.model.get('scope')}</span>`;
            }

            return extraInfo;
        },

        getEntityFields(entity) {
            let result = {};
            let notAvailableTypes = [
                'address',
                'available-currency',
                'attachmentMultiple',
                'currencyConverted',
                'email',
                'file',
                'linkParent',
                'personName',
                'phone'
            ];
            let notAvailableFieldsList = [
                'createdAt',
                'modifiedAt'
            ];
            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                result.id = {
                    type: 'varchar'
                };
                Object.keys(fields).forEach(name => {
                    let field = fields[name];
                    if (!field.disabled && !notAvailableFieldsList.includes(name) && !notAvailableTypes.includes(field.type) && !field.importDisabled) {
                        result[name] = field;
                    }
                });
            }
            return result;
        },

    })
);