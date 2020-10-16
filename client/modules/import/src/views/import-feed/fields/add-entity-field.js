

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

Espo.define('import:views/import-feed/fields/add-entity-field', 'view',
    Dep => Dep.extend({

        template: 'import:import-feed/fields/add-entity-field/base',

        fields: [],

        events: {
            'click button[data-action="actionAddEntityField"]': function () {
                this.actionAddField();
            }
        },

        data() {
            return {
                name: this.name,
                label: this.options.label
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.fields = Object.keys(this.options.fields || {});
        },

        actionAddField() {
            if (this.getParentView().mode === 'edit') {
                let options = this.fields.filter(field => !this.options.selectedFields.includes(field));
                let translatedOptions = {};
                options.forEach(field => {
                    let label = this.translate(field, 'fields', this.options.entity);
                    let type = (this.options.fields || {})[field].type;
                    if (type) {
                        type = this.translate(type, 'fieldTypes', 'Admin');
                        label = `${label} (${type})`;
                    }
                    translatedOptions[field] = label;
                });

                this.createView('addModal', 'import:views/import-feed/modals/array-entity-add', {
                    scope: this.options.entity,
                    options: options,
                    translatedOptions: translatedOptions
                }, view => {
                    view.render();
                    this.listenTo(view, 'add', data => {
                        if (!this.options.selectedFields.includes(data.value)) {
                            this.options.selectedFields.push(data.value);
                            view.options.options = (this.fields || []).filter(item => !this.options.selectedFields.includes(item));
                            this.trigger('addField', data);
                            view.reRender();
                        }
                    }, this);
                });
            }
        }
    })
);