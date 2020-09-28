

Espo.define('import:views/import-feed/modals/run-import-options', 'views/modal',
    Dep => Dep.extend({

        template: 'import:import-feed/modals/run-import-options',

        data() {
            return {
                scope: this.scope
            }
        },

        setup() {
            this.buttonList = [
                {
                    name: 'runImport',
                    label: 'runImport',
                    style: 'primary',
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            this.scope = this.options.scope || this.model.name || this.scope;
            this.header = this.getLanguage().translate('runImport', 'labels', this.scope);
            this.setupFields();
        },

        setupFields() {
            this.createView('importFile', 'views/fields/file', {
                el: `${this.options.el} .field[data-name="importFile"]`,
                model: this.model,
                name: 'importFile',
                params: {
                    required: true,
                    accept: ['.csv']
                },
                mode: 'edit',
                inlineEditDisabled: true
            }, view => {
                this.listenTo(this, 'close', () => {
                    view.deleteAttachment();
                });
            });
        },

        actionRunImport() {
            if (this.validate()) {
                this.notify('Not valid', 'error');
                return;
            }

            let data = {
                importFeedId: this.model.id || null,
                attachmentId: this.model.get('importFileId') || null
            };
            this.notify('Loading...');
            this.ajaxPostRequest('ImportFeed/action/RunImport', data).then(response => {
                if (response) {
                    this.notify(this.translate('importRunning', 'labels', 'ImportFeed'), 'success');
                    this.dialog.close();
                    this.model.trigger('importRun');
                    setTimeout(() => {
                        Backbone.trigger('showQueuePanel');
                    }, 2000);
                }
            });
        },

        validate() {
            let notValid = false;
            let fields = this.nestedViews;
            for (let i in fields) {
                if (fields[i].mode === 'edit') {
                    if (!fields[i].disabled && !fields[i].readOnly) {
                        notValid = fields[i].validate() || notValid;
                    }
                }
            }
            return notValid
        },
    })
);
