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

Espo.define('import:views/import-configurator-item/fields/import-by', 'views/fields/multi-enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.prepareImportByOptions();
            this.listenTo(this.model, 'change:name', () => {
                this.prepareImportByOptions(() => {
                    this.reRender();
                });
            });
        },

        isRequired: function () {
            return this.params.options.length > 0;
        },

        prepareImportByOptions(callback) {
            this.params.options = [];
            this.translatedOptions = {};

            let foreignEntity = this.getMetadata().get(`entityDefs.${this.model.get('entity')}.links.${this.model.get('name')}.entity`);
            if (foreignEntity) {
                this.params.options.push('id');
                this.translatedOptions['id'] = this.translate('id', 'fields', 'Global');

                $.each(this.getMetadata().get(`entityDefs.${foreignEntity}.fields`) || {}, (name, data) => {
                    if (
                        data.type
                        && ['bool', 'enum', 'varchar', 'float', 'int', 'text', 'wysiwyg'].includes(data.type)
                        && !data.disabled
                        && !data.importDisabled
                    ) {
                        this.params.options.push(name);
                        this.translatedOptions[name] = this.translate(name, 'fields', foreignEntity);
                    }
                });
            }

            if (callback) {
                callback();
            }
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.isRequired()) {
                this.show();
            } else {
                this.hide();
            }
        },

    })
);