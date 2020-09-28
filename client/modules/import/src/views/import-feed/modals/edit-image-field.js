

Espo.define('import:views/import-feed/modals/edit-image-field', 'views/modal',
    Dep => Dep.extend({

        template: 'import:import-feed/modals/edit-image-field',

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
                this.model.set({
                    pimImage: true,
                    entity: this.scope,
                    name: 'image'
                });
            });

            this.createBaseFields();
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.checkChannelsVisibility();
        },

        createBaseFields() {
            this.createView('default', 'import:views/import-feed/fields/value-container', {
                model: this.model,
                name: 'default',
                el: `${this.options.el} .field[data-name="default"]`,
                mode: 'edit',
                params: {
                    required: this.model.getFieldParam('default', 'required')
                }
            }, view => {});

            this.createView('column', 'import:views/import-feed/fields/column', {
                model: this.model,
                name: 'column',
                el: `${this.options.el} .field[data-name="column"]`,
                mode: 'edit',
                params: {
                    options: ['', ...this.options.fileColumns.map(item => item.column.toString())],
                    translatedOptions: this.options.fileColumns.reduce((prev, curr) => {
                        prev[curr.column.toString()] = curr.name;
                        return prev;
                    }, {'': ''})
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
                    this.checkChannelsVisibility();
                });
            });

            this.createView('channels', 'views/fields/link-multiple', {
                model: this.model,
                name: 'channels',
                el: `${this.options.el} .field[data-name="channels"]`,
                mode: 'edit',
                foreignScope: 'Channel',
                params: {
                    required: true
                },
                labelText: this.translate('channels', 'fields', 'Product')
            }, view => {});
        },

        checkChannelsVisibility() {
            let channels = this.getView('channels');
            if (this.model.get('scope') === 'Channel') {
                channels.params.required = true;
                channels.show();
            } else {
                channels.params.required = false;
                channels.hide();
            }
            channels.reRender();
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
