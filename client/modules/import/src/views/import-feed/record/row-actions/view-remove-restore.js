

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

Espo.define('import:views/import-feed/record/row-actions/view-remove-restore', 'views/record/row-actions/view-and-remove', function (Dep) {

    return Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            const first = this.model.collection.models[0];
            this.model.isRestorable = !!(first && first.id === this.model.id);
        },

        getActionList: function () {
            const actionList = Dep.prototype.getActionList.call(this);

            if (this.model.isRestorable) {
                actionList.push({
                    action: 'restore',
                    label: 'Restore'
                });
            }

            return actionList;
        },

        actionRestore: function () {
            this.confirm({
                message: this.translate('doYouReallyWantToRestoreData', 'messages', 'ImportResult'),
                confirmText: 'Restore'
            }, function () {
                $.ajax({
                    url: `ImportResult/action/restore`,
                    type: 'POST',
                    data: JSON.stringify({
                        id: this.model.id
                    }),
                    contentType: 'application/json',
                    success: function () {
                        setTimeout(() => {
                            Backbone.trigger('showQueuePanel');
                        }, 2000);
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        }
    });
});
