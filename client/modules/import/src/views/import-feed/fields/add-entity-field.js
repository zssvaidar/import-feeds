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