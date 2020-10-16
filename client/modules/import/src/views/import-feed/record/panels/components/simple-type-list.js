

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

                let view = model.get('pimImage') ? this.options.configImageView : this.options.configModalView;
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