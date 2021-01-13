

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

Espo.define('import:views/import-feed/modals/edit-configurator-field', 'views/modal',
    Dep => Dep.extend({

        template: 'import:import-feed/modals/edit-configurator-field',

        allowedFields: [],

        initialAttributes: {},

        withoutDefault: ['id'],

        data() {
            return {
                isAttribute: this.options.isAttribute
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.scope = this.options.scope;

            this.buttonList.push({
                name: 'save',
                label: 'Save',
                style: 'primary'
            },
            {
                name: 'cancel',
                label: 'Cancel'
            });

            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create', 'labels', this.scope);
            } else {
                this.header = this.getLanguage().translate('Edit');
                this.initialAttributes = this.model.getClonedAttributes();
            }

            this.getModelFactory().create(null, model => {
                if (this.model) {
                    model = this.model.clone();
                    model.id = this.model.id;
                    model.defs = this.model.defs;
                }
                this.model = model;
                this.model.set({entity: this.scope});
            });

            this.entityFields = this.options.entityFields || {};
            this.selectedFields = this.options.selectedFields || [];
            this.fileColumns = this.options.fileColumns || [];
            this.isAttribute = this.options.isAttribute;
            this.setAllowedFields();

            this.createBaseFields();

            this.listenTo(this.model, 'change:attributeId change:name', () => {
                this.applyDynamicChanges();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.applyDynamicChanges();
        },

        createBaseFields() {
            if (!this.isAttribute) {
                if (!this.id) {
                    this.model.set({name: this.allowedFields[0]});
                }

                const translatedOptions = this.allowedFields
                    .reduce((prev, curr) => {
                        prev[curr] = this.translate(curr, 'fields', this.scope);
                        return prev;
                    }, {});

                const options = this.allowedFields
                    .sort((prev, curr) => translatedOptions[prev].localeCompare(translatedOptions[curr]));

                this.createView('name', 'views/fields/enum', {
                    model: this.model,
                    name: 'name',
                    el: `${this.options.el} .field[data-name="name"]`,
                    mode: 'edit',
                    params: {
                        options: options,
                        translatedOptions: translatedOptions,
                        required: true,
                        readOnly: this.model.getFieldParam('default', 'required')
                    }
                }, view => {});
            } else {
                if (this.model.get('attributeId')) {
                    this.model.set({attributeName: this.model.get('name')});
                }
                this.createView('attribute', 'import:views/import-feed/fields/attribute', {
                    model: this.model,
                    name: 'attribute',
                    el: `${this.options.el} .field[data-name="attribute"]`,
                    mode: 'edit',
                    params: {
                        required: true
                    },
                    foreignScope: 'Attribute'
                }, view => {});
            }

            this.createView('column', 'import:views/import-feed/fields/column-container', {
                model: this.model,
                name: 'column',
                el: `${this.options.el} .field[data-name="column"]`,
                mode: 'edit',
                params: {
                    options: ['', ...this.fileColumns.map(item => item.column.toString())],
                    translatedOptions: this.fileColumns.reduce((prev, curr) => {
                        prev[curr.column.toString()] = curr.name;
                        return prev;
                    }, {'': ''})
                }
            }, view => {});

            this.createView('default', 'import:views/import-feed/fields/value-container', {
                isAttribute: this.isAttribute,
                model: this.model,
                name: 'default',
                el: `${this.options.el} .field[data-name="default"]`,
                mode: 'edit'
            }, view => {});

            this.createView('field', 'views/fields/enum', {
                model: this.model,
                name: 'field',
                el: `${this.options.el} .field[data-name="field"]`,
                mode: 'edit',
                params: {
                    options: [],
                    translatedOptions: {}
                }
            }, view => {});

            this.createView('scope', 'views/fields/enum', {
                model: this.model,
                name: 'scope',
                el: `${this.options.el} .field[data-name="scope"]`,
                mode: 'edit',
                params: {
                    options: ['Global', 'Channel']
                }
            }, view => {
                this.listenTo(this.model, 'change:scope', () => {
                    this.checkChannelVisibility();
                });
            });

            this.createView('channel', 'views/fields/link', {
                model: this.model,
                name: 'channel',
                el: `${this.options.el} .field[data-name="channel"]`,
                mode: 'edit',
                foreignScope: 'Channel',
                params: {
                    required: true
                },
                labelText: this.translate('channel', 'fields', 'ProductAttributeValue')
            }, view => {});

            const options = this.getConfig().get('inputLanguageList') || [];
            const translatedOptions = options.reduce((prev, curr) => {
                prev[curr] = this.getLanguage().translateOption(curr, 'language', 'Global');
                return prev;
            }, {'': this.getLanguage().translateOption('main', 'language', 'ImportFeed')});

            this.createView('locale', 'views/fields/enum', {
                model: this.model,
                name: 'locale',
                el: `${this.options.el} .field[data-name="locales"]`,
                mode: 'edit',
                params: {
                    options: ['', ...options],
                    translatedOptions: translatedOptions
                }
            }, view => {});
        },

        applyDynamicChanges() {
            if (!this.isAttribute && !(this.scope === 'Product' && this.model.get('name') === 'productCategories')) {
                this.getView('scope').hide();
                this.getView('scope').setDisabled();
            } else {
                this.getView('scope').show();
                this.getView('scope').setNotDisabled();
            }
            this.checkChannelVisibility();

            if (!(this.model.get('attributeId') && (this.model.get('isMultilang') || this.model.get('locale'))
                && !['enum','multiEnum'].includes(this.model.get('type')))) {
                this.getView('locale').setDisabled();
                this.getView('locale').hide();
            } else {
                this.getView('locale').setNotDisabled();
                this.getView('locale').show();
            }

            let field = this.getView('field');
            let translatedOptions = this.getTranslatesForImportByField();
            let options = Object.keys(translatedOptions);
            if (options.length && !this.isAttribute && ['link', 'linkMultiple'].includes(this.model.getFieldType('default'))) {
                field.params.options = options;
                field.translatedOptions = translatedOptions;
                field.reRender();
                field.show();
            } else {
                field.hide();
            }

            // hide default field
            if (this.withoutDefault.includes(this.model.get('name'))) {
                this.getView('default').hide();
                this.getView('default').setDisabled();
            } else {
                this.getView('default').show();
                this.getView('default').setNotDisabled();
            }
        },

        checkChannelVisibility() {
            let channel = this.getView('channel');
            if (this.model.get('scope') === 'Channel') {
                channel.params.required = true;
                channel.show();
                channel.setNotDisabled();
            } else {
                channel.params.required = false;
                channel.hide();
                channel.setDisabled();
            }
            channel.reRender();
        },

        getTranslatesForImportByField() {
            let result = {};
            let entity = this.model.getLinkParam('default', 'entity');
            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                result = Object.keys(fields)
                    .filter(name => ['varchar'].includes(fields[name].type) && !fields[name].layoutDetailDisabled)
                    .reduce((prev, curr) => {
                        prev[curr] = this.translate(curr, 'fields', entity);
                        return prev;
                    }, {'id': this.translate('id', 'fields', 'Global')});
            }
            return result;
        },

        setAllowedFields() {
            this.allowedFields = Object.keys(this.entityFields).filter(item => !this.selectedFields.includes(item));
            let currentField = this.model.get('name');
            if (currentField && !this.allowedFields.includes(currentField)) {
                this.allowedFields.unshift(currentField);
            }
        },

        fetch() {
            let data = {};
            let fields = this.nestedViews;
            for (let i in fields) {
                let view = fields[i];
                if (view.mode === 'edit' && !view.disabled && !view.readOnly && view.isFullyRendered()) {
                    _.extend(data, view.fetch());
                }
            }
            return data;
        },

        actionSave() {
            if (this.validate()) {
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }
            let data = this.fetch();
            this.model.set(data, {silent: true});
            this.trigger('after:save', this.model);
            this.dialog.close();
        },

        validate() {
            let notValid = false;
            let fields = this.nestedViews;
            for (let i in fields) {
                notValid = fields[i].validate() || notValid;
            }
            return notValid
        },

        afterNotValid() {
            this.notify('Not valid', 'error');
        }

    })
);
