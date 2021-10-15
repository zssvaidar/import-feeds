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

        prepareImportByOptions(callback) {
            this.params.options = [];
            this.translatedOptions = {};

            let translatedOptions = this.getTranslatesForImportByField();
            let options = Object.keys(translatedOptions);

            if (options.length && !this.isAttribute && ['asset', 'link', 'linkMultiple'].includes(this.model.getFieldType('default'))) {
                this.params.options = options;
                this.translatedOptions = translatedOptions;
            }

            if (callback) {
                callback();
            }
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.params.options.length > 0) {
                this.show();
            } else {
                this.hide();
            }
        },

        getTranslatesForImportByField() {
            let result = {};
            let entity = this.model.getLinkParam('default', 'entity');
            if (entity) {
                let fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                result = Object.keys(fields)
                    .filter(name => !['jsonObject', 'linkMultiple'].includes(fields[name].type) && !fields[name].disabled && !fields[name].importDisabled)
                    .reduce((prev, curr) => {
                        prev[curr] = this.translate(curr, 'fields', entity);
                        return prev;
                    }, {'id': this.translate('id', 'fields', 'Global')});
            }

            if (this.model.get('entity') === 'Product' && entity === 'Asset') {
                result['channel'] = this.translate('channelCode', 'labels', 'ImportFeed');
            }

            return result;
        },

    })
);