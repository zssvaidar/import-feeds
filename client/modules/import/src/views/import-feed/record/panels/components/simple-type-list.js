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

Espo.define('import:views/import-feed/record/panels/components/simple-type-list', 'views/record/list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'update-column-options', translatedOptions => this.updateOptionsInColumns(translatedOptions));
        },

        updateOptionsInColumns(translatedOptions) {
            Object.keys(this.nestedViews).forEach(name => {
                let rowView = this.nestedViews[name];
                if (rowView) {
                    let columnView = rowView.getView('columnField');
                    if (columnView) {
                        columnView.setOptionList(['', ...Object.keys(translatedOptions)]);
                        columnView.translatedOptions = _.extend({'': ''}, translatedOptions);
                        columnView.reRender();
                    }
                }
            });
        },

        prepareInternalLayout(internalLayout, model) {
            Dep.prototype.prepareInternalLayout.call(this, internalLayout, model);

            internalLayout.forEach(item => item.options.mode = this.options.mode || item.options.mode);
        },

        setDetailMode() {
            this.mode = 'detail';
            this.updateModeInFields(this.mode);
        },

        setEditMode() {
            this.mode = 'edit';
            this.updateModeInFields(this.mode);
        },

        updateModeInFields(mode) {
            Object.keys(this.nestedViews).forEach(row => {
                let rowView = this.nestedViews[row];
                if (rowView) {
                    Object.keys(rowView.nestedViews).forEach(field => {
                        let fieldView = rowView.nestedViews[field];
                        if (fieldView && typeof fieldView.setMode === 'function' && !fieldView.readOnly && !fieldView.disabled) {
                            fieldView.setMode(mode);
                            fieldView.reRender();
                        }
                    });
                }
            });
        },

        actionQuickEdit(data) {
            data = data || {};
            let id = data.id;
            if (!id) return;

            let model = this.collection.get(id);
            if (model && this.scope) {
                this.notify(this.translate('loading', 'messages'));

                let view = this.options.configModalView;
                this.createView('modal', view, {
                    isAttribute: !!model.get('attributeId'),
                    scope: model.get('entity'),
                    id: data.id,
                    model: model,
                    entityFields: this.getParentView().entityFields,
                    selectedFields: this.getParentView().selectedFields,
                    fileColumns: this.getParentView().fileColumns
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
            }
        },

        actionQuickRemove(data) {
            data = data || {};
            let id = data.id;
            if (!id) return;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, () => {
                let model = this.collection.get(id);
                if (model) {
                    this.removeRecordFromList(id);
                }
                this.collection.trigger('configuration-update');
            });
        }

    })
);