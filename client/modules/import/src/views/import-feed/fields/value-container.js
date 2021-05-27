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

Espo.define('import:views/import-feed/fields/value-container', 'views/fields/base',
    Dep => Dep.extend({

        listTemplate: 'import:import-feed/fields/value-container/base',

        detailTemplate: 'import:import-feed/fields/value-container/base',

        editTemplate: 'import:import-feed/fields/value-container/base',

        typesWithDefaultHash: ['password', 'text', 'varchar'],

        setup() {
            this.defs = this.options.defs || {};
            this.name = this.options.name || this.defs.name;
            this.params = this.options.params || this.defs.params || {};

            this.createDefaultField();

            this.listenTo(this.model, 'change:name change:attributeId', () => {
                this.clearDefaultDataFields();
                this.createDefaultField();
            });
        },

        createDefaultField() {
            this.clearView('default');

            let type;
            if (!this.options.isAttribute && !this.model.get('attributeId')) {
                let name = this.model.get('name');
                this.updateModelDefs(name);
                this.extendFieldParams(name);

                type = this.model.getFieldType(this.name);
                this.updateModelAttributes(type);
            } else if (this.model.get('attributeId')) {
                type = this.model.get('type');
                this.params.options = this.model.get('options');
                this.params.measure = (this.model.get('options') || [])[0];
            }

            this.createView('default', this.getValueFieldView(type), {
                el: `${this.options.el} > .field[data-name="default"]`,
                model: this.model,
                name: this.name,
                mode: this.mode,
                defs: this.defs,
                params: this.params,
                inlineEditDisabled: true,
                createDisabled: true,
                labelText: this.translate('default', 'fields', 'ImportFeed')
            }, view => {
                if (this.isRendered()) {
                    view.render();
                }
            });
        },

        getValueFieldView(type) {
            type = type || 'base';
            let view;
            if (type === 'image') {
                view = 'import:views/import-feed/fields/default-image';
            }
            return view || this.model.getFieldParam('default', 'view') || this.getFieldManager().getViewName(type);
        },

        updateModelDefs(name) {
            let entityDefs = this.getMetadata().get(['entityDefs', this.model.get('entity')]) || {};

            let baseFieldsDefs = (entityDefs.fields || {})[name] || {};
            let extraFieldsDefs = {
                readOnly: false,
                audited: false
            };

            let baseLinksDefs = (entityDefs.links || {})[name] || {};
            let extraLinksDefs = {};
            let customEntity = this.getMetadata().get(['clientDefs', 'ImportFeed', 'customEntities', name, 'entity']);
            if (customEntity) {
                extraLinksDefs.entity = customEntity;
            }

            this.model.setDefs({
                fields: {
                    default: _.extend({}, baseFieldsDefs, extraFieldsDefs, {required: false})
                },
                links: {
                    default: _.extend({}, baseLinksDefs, extraLinksDefs)
                }
            });
        },

        extendFieldParams(name) {
            let options = this.model.getFieldParam(this.name, 'options') || [];
            let translatedOptions = options.reduce((prev, curr) => {
                prev[curr] = this.getLanguage().translateOption(curr, name, this.model.get('entity'));
                return prev
            }, {'': ''});
            _.extend(this.params, {
                required: this.model.getFieldParam(this.name, 'required'),
                translatedOptions: translatedOptions
            });
        },

        updateModelAttributes(type) {
            this.getFieldManager().getActualAttributeList(type, this.name).forEach(key => {
                let value = this.getFormattedDefaultValue(key, type);
                value = this.modifyValueByType(type, value, key);
                this.model.set({[key]: value});
            });
            this.getFieldManager().getNotActualAttributeList(type, this.name).forEach(key => {
                let value = this.model.get(key) || (this.model.get('customData') || {})[key];
                this.model.set({[key]: value});
            });
        },

        clearDefaultDataFields() {
            let fields = {default: null};
            let type = this.model.getFieldType(this.name);
            this.getFieldManager().getActualAttributeList(type, this.name).forEach(key => {
                fields[key] = null;
            });
            this.getFieldManager().getNotActualAttributeList(type, this.name).forEach(key => {
                fields[key] = null;
                fields.customData = _.extend({}, this.model.get('customData'), {[key]: null});
            });
            this.model.set(fields);
        },

        getFormattedDefaultValue(key, type) {
            let result = this.model.get(key);
            if (typeof result === 'undefined' || result === null) {
                result = this.model.get(this.name);
                if (typeof result === 'undefined' || result === null) {
                    let defaultValue = this.model.getFieldParam(this.name, 'default');
                    if (typeof defaultValue !== 'undefined') {
                        result = defaultValue;
                    }
                }
            }

            if (!result && this.model.getFieldParam(this.name, 'required') && this.typesWithDefaultHash.includes(type)) {
                result = '{{hash}}';
            }

            return result;
        },

        modifyValueByType(type, value, key) {
            if (type === 'linkMultiple' && typeof value === 'string') {
                value = value.split(',');
            } else if (['unit', 'currency'].includes(type)) {
                value = this.model.get(key);
            }
            return value;
        },

        fetch() {
            let data = {};
            let view = this.getView('default');
            if (view) {
                _.extend(data, view.fetch());
            }
            return data;
        },

        validate() {
            return this.validateColumn();
        },

        validateColumn() {
            let validate = false;
            let keys = this.getFieldManager().getActualAttributeList(this.model.getFieldType(this.name), this.name).map(key => key);
            validate = !this.checkValueExists('column') && !keys.every(key => this.checkValueExists(key));
            if (validate) {
                let column = this.translate('column', 'fields', 'ImportFeed');
                let defaultValue = this.translate('default', 'fields', 'ImportFeed');
                let msg = this.translate('columnOrDefaultValueIsRequired', 'messages', 'ImportFeed')
                    .replace('{column}', column)
                    .replace('{default}', defaultValue);
                this.showValidationMessage(msg);
                this.trigger('invalid');
            }
            return validate;
        },

        checkValueExists(key) {
            return this.model.has(key) && typeof this.model.get(key) !== 'undefined' && this.model.get(key) !== null && this.model.get(key);
        },

        setMode(mode) {
            Dep.prototype.setMode.call(this, mode);

            let defaultField = this.getView('default');
            if (defaultField) {
                defaultField.setMode(mode);
            }
        }

    })
);