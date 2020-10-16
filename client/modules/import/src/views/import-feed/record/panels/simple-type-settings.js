

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

Espo.define('import:views/import-feed/record/panels/simple-type-settings', 'views/record/panels/bottom',
    Dep => Dep.extend({

        template: 'import:import-feed/record/panels/simple-type-settings',

        configListView: 'import:views/import-feed/record/panels/components/simple-type-list',

        configRowActionsView: 'import:views/import-feed/record/panels/components/simple-type-row-actions',

        configModalView: 'import:views/import-feed/modals/edit-configurator-field',

        configImageView: 'import:views/import-feed/modals/edit-image-field',

        configuratorFields: ['entity', 'delimiter', 'idField'],

        validations: ['configurator', 'delimiters'],

        initialData: null,

        configData: null,

        defaultEntity: 'Product',

        defaultDelimiter: ';',

        entitiesList: [],

        entityFields: {},

        fileColumns: [],

        panelModel: null,

        selectedFields: [],

        forbiddenSelectedFieldsTypes: ["image"],

        events: _.extend({
            'click button[data-name="configuratorActions"]': function (e) {
                let actions = this.getConfiguratorActions();
                if (actions.length === 1) {
                    e.stopPropagation();
                    this.actionAddEntityField();
                }
            }
        }, Dep.prototype.events),

        setup() {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.loadFileColumns(() => {
                this.loadConfiguration();
                this.initialData = Espo.Utils.cloneDeep(this.configData);
                this.createConfiguratorFields();
                this.createConfiguratorList();
                this.wait(false);
            });

            this.listenTo(this.model, 'fileUpdate change:fileFieldDelimiter change:fileTextQualifier change:isFileHeaderRow', () => {
                this.loadFileColumns(null, true);
            });

            this.listenTo(this.model, 'change:fileDataAction', () => {
                this.updateIdFieldOptions();
            });

            this.listenTo(this.model, 'after:save', () => {
                this.configData = this.getConfigurationData();
                this.initialData = Espo.Utils.cloneDeep(this.configData);
            });

            this.listenTo(this.model, 'cancel:save', () => {
                this.cancelEdit();
            });
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.scope = this.model.name;
            data.configuratorActions = this.getConfiguratorActions();
            return data;
        },

        loadFileColumns(callback, withRowsUpdating) {
            let fileId = this.model.get('fileId');
            let promise = null;
            if (fileId) {
                promise = this.ajaxGetRequest(`ImportFeed/${this.model.get('fileId')}/fileColumns?delimiter=` +
                    `${this.model.get('fileFieldDelimiter')}&enclosure=${this.model.get('fileTextQualifier')}` +
                    `&isHeaderRow=${this.model.get('isFileHeaderRow') ? 1 : 0}`);
            } else {
                promise = new Promise(resolve => resolve([]));
            }
            promise.then(response => {
                this.updateFileColumns(response, withRowsUpdating);
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }, error => {
                if (callback && typeof callback === 'function') {
                    callback();
                }
            });
        },

        loadConfiguration(entity) {
            this.entitiesList = this.getEntitiesList();
            let data = this.model.get('data');

            if (!entity && Espo.Utils.isObject(data)) {
                this.entityFields = this.getEntityFields(data.entity);
            } else {
                data = {};
                data.delimiter = this.defaultDelimiter;
                data.entity = entity || (this.entitiesList.includes(this.defaultEntity) ? this.defaultEntity : this.entitiesList[0]);
                this.entityFields = this.getEntityFields(data.entity);
                data.configuration = this.getEntityConfiguration(data.entity);
                data.idField = Object.keys(this.getTranslatedOptionsForIdField())[0];
            }
            this.configData = data;
        },

        getEntitiesList() {
            let scopes = this.getMetadata().get('scopes') || {};
            return Object.keys(scopes)
                .filter(scope => scopes[scope].importable && scopes[scope].entity)
                .sort((v1, v2) => this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural')));
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
                    if (
                        !field.customizationDisabled && !field.disabled && !field.notStorable && !!field.type
                        && !notAvailableFieldsList.includes(name) && (name === 'code' || !field.emHidden)
                        && (!field.importDisabled && (!notAvailableTypes.includes(field.type) || ('importDisabled' in field))
                        && !(field.multilangField && ['enum', 'multiEnum'].includes(field.type)))
                    ) {
                        result[name] = field;
                    }
                });
            }
            return result;
        },

        getEntityConfiguration(entity) {
            let result = [];
            Object.keys(this.entityFields).forEach(name => {
                let field = this.entityFields[name];
                if (field.required) {
                    let data = {
                        name: name,
                        column: null,
                        default: null
                    };
                    if (['link', 'linkMultiple'].includes(field.type)) {
                        let options = {
                            foreign: this.getMetadata().get(['entityDefs', entity, 'links', name, 'entity']) || {},
                            field: 'id',
                            customData: {
                                fieldName: this.getLanguage().translate('id', 'fields', 'Global')
                            }
                        };
                        if (field.type === 'link') {
                            options.isLink = true;
                        } else {
                            options.isLinkMultiple = true;
                        }
                        _.extend(data, options);
                    }
                    result.push(data);
                }
            });
            return result;
        },

        getTranslatedOptionsForIdField() {
            this.setupSelected();
            let options = [];
            let translatedOptions = {};
            if (this.model.get('fileDataAction') === 'create_update') {
                options = ['', ...this.selectedFields];
            }
            if (this.model.get('fileDataAction') === 'update') {
                options = this.selectedFields;
            }

            options.forEach(field => translatedOptions[field] = this.translate(field, 'fields', this.panelModel.get('entity')));
            return translatedOptions;
        },

        setupSelected() {
            this.selectedFields = ((this.configData || {}).configuration || [])
                .filter(item => !item.attributeId && !(this.entityFields[item.name] || {}).importMultipleField && !this.forbiddenSelectedFieldsTypes.includes((this.entityFields[item.name] || {}).type))
                .map(item => item.name);
        },

        createConfiguratorFields() {
            this.getModelFactory().create(null, model => {
                this.panelModel = model;
                this.updatePanelModelAttributes();

                this.listenTo(this.panelModel, 'change:entity', () => {
                    this.loadConfiguration(this.panelModel.get('entity'));
                    this.updateIdFieldOptions();
                    this.updatePanelModelAttributes();
                    this.updateCollection();
                    this.createConfiguratorList();
                    this.reRender();
                });

                this.createView('entity', 'views/fields/enum', {
                    model: this.panelModel,
                    el: this.options.el + ' .field[data-name="entity"]',
                    defs: {
                        name: 'entity',
                        params: {
                            options: this.entitiesList,
                            translatedOptions: this.entitiesList.reduce((prev, curr) => {
                                prev[curr] = this.translate(curr, 'scopeNames');
                                return prev;
                            }, {})
                        },
                    },
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => {
                    view.render();
                });

                this.createView('delimiter', 'views/fields/varchar', {
                    model: this.panelModel,
                    el: this.options.el + ' .field[data-name="delimiter"]',
                    name: 'delimiter',
                    inlineEditDisabled: true,
                    mode: this.mode
                }, view => {
                    view.render();
                });

                let translatedOptions = this.getTranslatedOptionsForIdField();
                this.createView('idField', 'views/fields/enum', {
                    model: this.panelModel,
                    el: this.options.el + ' .field[data-name="idField"]',
                    name: 'idField',
                    mode: this.mode,
                    params: {
                        options: Object.keys(translatedOptions),
                        translatedOptions: translatedOptions,
                        required: this.model.get('fileDataAction') === 'update'
                    },
                    inlineEditDisabled: true
                }, view => {
                    view.listenTo(view, 'after:render', () => {
                        if (this.model.get('fileDataAction') === 'create') {
                            view.hide();
                        } else {
                            view.show();
                        }
                    });
                    view.render();
                });
            });
        },

        updatePanelModelAttributes() {
            this.panelModel.set({
                entity: (this.configData || {}).entity,
                delimiter: (this.configData || {}).delimiter,
                idField: (this.configData || {}).idField,
            }, {silent: true});
        },

        createConfiguratorList() {
            this.clearView('configurator');
            this.getCollectionFactory().create('ImportFeed', collection => {
                this.collection = collection;
                this.updateCollection();
                let listLayout = this.getCollectionLayout();

                this.listenTo(this.collection, 'configuration-update', model => {
                    if (model) {
                        this.updateModelInCollection(model);
                    }
                    this.configData = this.getConfigurationData();
                    this.updateIdFieldOptions();
                    if (this.mode !== 'edit') {
                        this.save(() => this.createConfiguratorList());
                    } else {
                        this.createConfiguratorList();
                    }
                });

                this.createView('configurator', this.configListView, {
                    collection: collection,
                    el: `${this.options.el} .list-container`,
                    listLayout: listLayout,
                    checkboxes: false,
                    massActionsDisabled: true,
                    showMore: false,
                    buttonsDisabled: true,
                    rowActionsView: this.configRowActionsView,
                    configModalView: this.configModalView,
                    configImageView: this.configImageView,
                    mode: this.mode,
                    entityFields: this.entityFields,
                    selectedFields: this.selectedFields,
                    fileColumns: this.fileColumns
                }, view => {
                    view.render();
                });
            });
        },

        updateCollection() {
            this.collection.reset();

            let configuration = (this.configData || {}).configuration || [];
            this.collection.total = configuration.length;
            configuration.forEach((item, i) => {
                this.getModelFactory().create(null, model => {
                    model.set(_.extend(item, {entity: this.panelModel.get('entity')}));

                    model.id = i + 1;
                    this.collection.add(model);
                    this.collection._byId[model.id] = model;
                });
            });
        },

        getCollectionLayout() {
            return [
                {
                    name: 'name',
                    customLabel: this.translate('Field', 'labels', 'Import'),
                    notSortable: true,
                    type: 'varchar',
                    params: {
                        readOnly: true,
                        inlineEditDisabled: true
                    },
                    view: 'import:views/import-feed/fields/varchar-with-info'
                },
                {
                    name: 'column',
                    notSortable: true,
                    type: 'enum',
                    params: {
                        options: ['', ...this.fileColumns.map(item => item.column.toString())],
                        translatedOptions: this.fileColumns.reduce((prev, curr) => {
                            prev[curr.column.toString()] = curr.name;
                            return prev;
                        }, {'': ''}),
                        inlineEditDisabled: true
                    },
                    view: 'import:views/import-feed/fields/column'
                },
                {
                    name: 'default',
                    notSortable: true,
                    type: 'base',
                    params: {
                        inlineEditDisabled: true
                    },
                    view: 'import:views/import-feed/fields/value-container'
                }
            ];
        },

        updateFileColumns(response, withRowsUpdating) {
            this.fileColumns = response;
            if (withRowsUpdating) {
                let translatedOptions = this.fileColumns.reduce((prev, curr) => {
                    prev[curr.column.toString()] = curr.name;
                    return prev;
                }, {});
                this.getView('configurator').trigger('update-column-options', translatedOptions);
            }
        },

        getConfiguratorActions() {
            let configuratorActions = [{
                action: 'addEntityField',
                label: this.translate('addEntityField', 'labels', 'ImportFeed')
            }];
            if (this.panelModel.get('entity') === 'Product') {
                configuratorActions.push({
                    action: 'addProductAttribute',
                    label: this.translate('addProductAttribute', 'labels', 'ImportFeed')
                },
                {
                    action: 'addProductImage',
                    label: this.translate('addProductImage', 'labels', 'ImportFeed')
                });
            }
            return configuratorActions;
        },

        updateIdFieldOptions() {
            let view = this.getView('idField');
            if (view) {
                let translatedOptions = this.getTranslatedOptionsForIdField();
                if (!Object.keys(translatedOptions).includes(this.panelModel.get('idField'))) {
                    this.panelModel.set({idField: Object.keys(translatedOptions)[0]}, {silent: true});
                }
                view.params.options = Object.keys(translatedOptions);
                view.translatedOptions = translatedOptions;
                if (this.model.get('fileDataAction') === 'update') {
                    view.setRequired();
                } else {
                    view.setNotRequired();
                }
                view.reRender();
            }
        },

        actionAddEntityField() {
            this.notify('Loading...');

            this.createView('modal', this.configModalView, {
                scope: this.panelModel.get('entity'),
                entityFields: this.entityFields,
                selectedFields: this.selectedFields,
                fileColumns: this.fileColumns
            }, view => {
                view.once('after:render', () => {
                    this.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', () => {
                    this.clearView('modal');
                });

                this.listenToOnce(view, 'after:save', m => this.collection.trigger('configuration-update', m));
            });
        },

        actionAddProductAttribute() {
            this.notify('Loading...');

            this.createView('modal', this.configModalView, {
                isAttribute: true,
                scope: 'Attribute',
                entityFields: this.entityFields,
                selectedFields: this.selectedFields,
                fileColumns: this.fileColumns
            }, view => {
                view.once('after:render', () => {
                    this.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', () => {
                    this.clearView('modal');
                });

                this.listenToOnce(view, 'after:save', m => this.collection.trigger('configuration-update', m));
            });
        },

        actionAddProductImage() {
            this.notify('Loading...');

            this.createView('modal', this.configImageView, {
                scope: 'PimImage',
                fileColumns: this.fileColumns
            }, view => {
                view.once('after:render', () => {
                    this.notify(false);
                });

                view.render();

                this.listenToOnce(view, 'remove', () => {
                    this.clearView('modal');
                });

                this.listenToOnce(view, 'after:save', m => this.collection.trigger('configuration-update', m));
            });
        },

        updateModelInCollection(m) {
            let model;
            if (m.id) {
                model = this.collection.get(m.id);
                model.set(m.getClonedAttributes(), {silent: true});
            } else {
                model = m;
                m.id = Math.max(...this.collection.map(model => model.id)) + 1;
                this.collection.add(m);
                this.collection._byId[m.id] = m;
                this.collection.total++;
            }
            this.getFieldConfiguration(model);
        },

        save(callback) {
            this.model.set({data: this.configData}, {silent: true});
            this.notify('Loading...');
            this.model.save({data: this.configData}, {
                success: () => {
                    this.notify('Saved', 'success');
                    this.initialData = Espo.Utils.cloneDeep(this.configData);
                    this.model.trigger('after:save');
                    callback();
                },
                error: () => {
                    this.cancelEdit();
                },
                patch: !this.model.isNew()
            });
        },

        getConfigurationData() {
            let data = {
                entity: this.panelModel.get('entity'),
                idField: this.model.get('fileDataAction') === 'create' ? null : this.panelModel.get('idField'),
                delimiter: this.panelModel.get('delimiter')
            };
            data.configuration = this.collection.map(model => this.getFieldConfiguration(model));
            return data;
        },

        validate() {
            for (let i in this.validations) {
                let method = 'validate' + Espo.Utils.upperCaseFirst(this.validations[i]);
                if (this[method].call(this)) {
                    this.trigger('invalid');
                    return true;
                }
            }
            return false;
        },

        validateConfigurator() {
            let validate = false;
            let configurator = this.getView('configurator');
            if (configurator) {
                Object.keys(configurator.nestedViews).forEach(row => {
                    let rowView = configurator.nestedViews[row];
                    if (rowView) {
                        ['columnField', 'defaultField'].forEach(field => {
                            let fieldView = rowView.getView(field);
                            if (fieldView && fieldView.mode === 'edit' && typeof fieldView.validate === 'function'
                                && !fieldView.disabled && !fieldView.readOnly) {
                                validate = fieldView.validate() || validate;
                            }
                        });
                    }
                });
            }
            return validate;
        },

        validateDelimiters() {
            let validate = false;
            if (this.model.get('fileFieldDelimiter') === this.panelModel.get('delimiter')) {
                let delimiter = this.getView('delimiter');
                delimiter.trigger('invalid');
                let msg = this.translate('delimitersMustBeDifferent', 'messages', 'ImportFeed');
                delimiter.showValidationMessage(msg);
                validate = true;
            }
            return validate;
        },

        fetch() {
            return {
                data: this.getConfigurationData()
            };
        },

        getFieldConfiguration(model) {
            let result = {
                name: model.get('name'),
                column: model.get('column'),
                default: model.get('default'),
                scope: model.get('scope'),
                channelsIds: model.get('channelsIds'),
                channelsNames: model.get('channelsNames')
            };
            let attributeId = model.get('attributeId');
            if (attributeId) {
                const extraConf = {
                    attributeId: attributeId,
                    name: model.get('attributeName') || model.get('name'),
                    type: model.get('type'),
                    options: model.get('options')
                };
                ['column', 'default'].forEach(item => {
                    this.getFieldManager().getActualAttributes(model.get('type'), item).forEach(key => {
                        _.extend(extraConf, {[key]: model.get(key)});
                    });
                });
                if (model.get('isMultilang') || model.get('locale')) {
                    extraConf.locale = model.get('locale');
                }
		_.extend(result, extraConf);
            } else if (model.get('pimImage')) {
                _.extend(result, {
                    pimImage: true,
                    default: model.get('defaultId'),
                    defaultName: model.get('defaultName')
                });
            } else {
                if (this.entityFields[model.get('name')]) {
                    result.locale = model.get('locale');
                }
                this.modifyFieldConfByType(result, model);
            }
            model.set(result, {silent: true});
            return result;
        },

        modifyFieldConfByType(configuration, model) {
            configuration = configuration || {};
            let extraConf = {};
            let type = model.getFieldType('default');
            if (['link', 'linkMultiple'].includes(type)) {
                let field = model.get('field');
                extraConf = {
                    foreign: model.getLinkParam('default', 'entity'),
                    field: field,
                    customData: {
                        fieldName: field === 'id' ? this.translate('id', 'fields', 'Global') :
                            this.translate(field, 'fields', model.getLinkParam('default', 'entity'))
                    }
                };
                if (type === 'link') {
                    _.extend(extraConf, {
                        default: model.get('defaultId'),
                        defaultName: model.get('defaultName'),
                        isLink: true
                    });
                } else {
                    _.extend(extraConf, {
                        default: (model.get('defaultIds') || []).join(','),
                        defaultNames: model.get('defaultNames'),
                        isLinkMultiple: true
                    });
                }
            } else if (type === 'image') {
                _.extend(extraConf, {
                    default: model.get('defaultId'),
                    defaultName: model.get('defaultName')
                });
            } else if (['unit', 'currency'].includes(type)) {
                ['column', 'default'].forEach(item => {
                    this.getFieldManager().getActualAttributes(type, item).forEach(key => {
                        _.extend(extraConf, {[key]: model.get(key)});
                    });
                });
            }
            _.extend(configuration, extraConf);
        },

        setDetailMode() {
            this.mode = 'detail';
            this.configuratorFields.forEach(field => {
                let view = this.getView(field);
                if (view) {
                    view.setMode('detail');
                    view.reRender();
                }
            });
            let configurator = this.getView('configurator');
            if (configurator) {
                configurator.setDetailMode();
            }
        },

        setEditMode() {
            this.mode = 'edit';
            this.configuratorFields.forEach(field => {
                let view = this.getView(field);
                if (view) {
                    view.setMode('edit');
                    view.reRender();
                }
            });
            let configurator = this.getView('configurator');
            if (configurator) {
                configurator.setEditMode();
            }
        },

        cancelEdit() {
            this.configData = Espo.Utils.cloneDeep(this.initialData);
            this.entityFields = this.getEntityFields(this.configData.entity);
            this.updateIdFieldOptions();
            this.updatePanelModelAttributes();
            this.updateCollection();
            this.createConfiguratorList();
            this.reRender();
        },

    })
);
