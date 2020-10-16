

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

Espo.define('import:views/import-feed/record/detail', 'import:views/record/detail',
    Dep => Dep.extend({

        getBottomPanels() {
            let bottomView = this.getView('bottom');
            if (bottomView) {
                return bottomView.nestedViews;
            }
            return null;
        },

        setDetailMode() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].setDetailMode === 'function') {
                        panels[panel].setDetailMode();
                    }
                }
            }
            Dep.prototype.setDetailMode.call(this);
        },

        setEditMode() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].setEditMode === 'function') {
                        panels[panel].setEditMode();
                    }
                }
            }
            Dep.prototype.setEditMode.call(this);
        },

        cancelEdit() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].cancelEdit === 'function') {
                        panels[panel].cancelEdit();
                    }
                }
            }
            Dep.prototype.cancelEdit.call(this);
        },


        save: function (callback, skipExit) {
            this.beforeBeforeSave();

            var data = this.fetch();

            var self = this;
            var model = this.model;

            var initialAttributes = this.attributes;

            var beforeSaveAttributes = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(beforeSaveAttributes), data);

            var attrs = false;
            if (model.isNew()) {
                attrs = data;
            } else {
                for (var name in data) {
                    if (typeof initialAttributes[name] === 'undefined' || _.isEqual(initialAttributes[name], data[name])) {
                        continue;
                    }
                    (attrs || (attrs = {}))[name] = data[name];
                }
            }

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            if (this.validatePanels()) {
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            let changesFromPanels = this.handlePanelsFetch();

            if (!attrs && !changesFromPanels) {
                this.trigger('cancel:save');
                this.afterNotModified();
                return true;
            }

            if (!attrs) {
                attrs = {};
            }

            attrs =  _.extend(attrs, changesFromPanels);

            model.set(attrs, {silent: true});

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            model.save(attrs, {
                success: function () {
                    this.afterSave();
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    this.trigger('after:save');
                    model.trigger('after:save');

                    if (!callback) {
                        if (!skipExit) {
                            if (self.isNew) {
                                this.exit('create');
                            } else {
                                this.exit('save');
                            }
                        }
                    } else {
                        callback(this);
                    }
                }.bind(this),
                error: function (e, xhr) {
                    var r = xhr.getAllResponseHeaders();
                    var response = null;

                    if (~[409, 500].indexOf(xhr.status)) {
                        var statusReasonHeader = xhr.getResponseHeader('X-Status-Reason');
                        if (statusReasonHeader) {
                            try {
                                var response = JSON.parse(statusReasonHeader);
                            } catch (e) {
                                console.error('Could not parse X-Status-Reason header');
                            }
                        }
                    }

                    if (response && response.reason) {
                        var methodName = 'errorHandler' + Espo.Utils.upperCaseFirst(response.reason.toString());
                        if (methodName in this) {
                            xhr.errorIsHandled = true;
                            this[methodName](response.data);
                        }
                    }

                    this.afterSaveError();

                    model.attributes = beforeSaveAttributes;
                    self.trigger('cancel:save');
                    model.trigger('cancel:save');

                }.bind(this),
                patch: !model.isNew()
            });
            return true;
        },

        handlePanelsFetch() {
            let changes = false;
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].fetch === 'function') {
                        changes = panels[panel].fetch() || changes;
                    }
                }
            }
            return changes;
        },

        validatePanels() {
            let notValid = false;
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    if (typeof panels[panel].validate === 'function') {
                        notValid = panels[panel].validate() || notValid;
                    }
                }
            }
            return notValid
        },

    })
);