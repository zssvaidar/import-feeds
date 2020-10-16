

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
