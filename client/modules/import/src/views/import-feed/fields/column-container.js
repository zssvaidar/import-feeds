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

Espo.define('import:views/import-feed/fields/column-container', 'views/fields/base',
    Dep => Dep.extend({

        listTemplate: 'import:import-feed/fields/column-container/base',
        detailTemplate: 'import:import-feed/fields/column-container/base',
        editTemplate: 'import:import-feed/fields/column-container/base',

        containerViews: {},

        data() {
            return {
                containerViews: this.containerViews
            }
        },

        setup() {
            this.defs = this.options.defs || {};
            this.name = this.options.name || this.defs.name;
            this.params = this.options.params || this.defs.params || {};

            this.createColumnFields();
            this.listenTo(this.model, 'change:name change:attributeId change:singleColumn', () => {
                this.createColumnFields();
                this.reRender();
            });
        },

        createColumnFields() {
            this.containerViews = {};
            this.containerViews['column'] = true;
            this.createView('column', 'import:views/import-feed/fields/column', {
                model: this.model,
                el: `${this.options.el} .field[data-name="column"]`,
                name: 'column',
                defs: this.defs,
                params: this.params,
                inlineEditDisabled: true,
                mode: this.mode
            });

            const fieldDefs = this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name')]) || {};
            const type = this.model.get('type') || this.model.getFieldType('default') || fieldDefs.type || 'base';

            if (type === 'currency') {
                this.createSingleColumnField(type);
                if (!this.model.get('singleColumn')) {
                    this.containerViews['columnCurrency'] = true;
                    this.createView('columnCurrency', 'import:views/import-feed/fields/column-currency', {
                        model: this.model,
                        el: `${this.options.el} .field[data-name="columnCurrency"]`,
                        name: 'columnCurrency',
                        defs: this.defs,
                        columnParams: this.params,
                        inlineEditDisabled: true,
                        mode: this.mode
                    });
                }
            }

            if (type === 'unit') {
                this.createSingleColumnField(type);
                if (!this.model.get('singleColumn')) {
                    this.containerViews['columnUnit'] = true;
                    this.createView('columnUnit', 'import:views/import-feed/fields/column-unit', {
                        model: this.model,
                        el: `${this.options.el} .field[data-name="columnUnit"]`,
                        name: 'columnUnit',
                        defs: this.defs,
                        columnParams: this.params,
                        inlineEditDisabled: true,
                        mode: this.mode
                    });
                }
            }
        },

        createSingleColumnField(type) {
            if (!this.model.has('singleColumn')) {
                this.model.set('singleColumn', true, {silent: true});
            }

            this.containerViews['singleColumn'] = true;
            this.createView('singleColumn', 'views/fields/bool', {
                model: this.model,
                el: `${this.options.el} .field[data-name="singleColumn"]`,
                name: 'singleColumn',
                inlineEditDisabled: true,
                mode: this.mode
            }, view => {
                view.listenToOnce(view, 'after:render', () => {
                    this.initTooltip('singleColumn' + type.charAt(0).toUpperCase() + type.slice(1));
                });
            });
        },

        initTooltip(name) {
            $a = this.$el.find('.single-column-info');
            $a.popover({
                placement: 'bottom',
                container: 'body',
                content: this.translate(name, 'tooltips', 'ImportFeed').replace(/(\r\n|\n|\r)/gm, '<br>'),
                trigger: 'click',
                html: true
            }).on('shown.bs.popover', function () {
                $('body').one('click', function () {
                    $a.popover('hide');
                });
            });
        },

        fetch() {
            let data = {};
            $.each(this.nestedViews, (name, view) => _.extend(data, view.fetch()));
            return data;
        },

        validate() {
            let validate = false;
            let view = this.getView('column');
            if (view) {
                validate = view.validate();
            }
            return validate;
        },

        setMode(mode) {
            Dep.prototype.setMode.call(this, mode);

            $.each(this.nestedViews, (name, view) => view.setMode(mode));
        }

    })
);