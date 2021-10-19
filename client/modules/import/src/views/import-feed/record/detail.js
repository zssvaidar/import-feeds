/*
 * Export Feeds
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

Espo.define('import:views/import-feed/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        template: 'import:import-feed/record/detail',

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'after:save', () => {
                this.handleButtonsDisability();
            });
        },

        data() {
            let data = Dep.prototype.data.call(this);

            data['importButtonsDisabled'] = this.isButtonsDisabled();

            return data;
        },

        isButtonsDisabled() {
            return !this.model.get('isActive') || !this.model.get('fileId');
        },

        handleButtonsDisability() {
            const $buttons = $('.import-actions');
            if (this.isButtonsDisabled()) {
                $buttons.addClass('disabled');
            } else {
                $buttons.removeClass('disabled');
            }
        },

        actionRunImport() {
            if ($('.action[data-action=runImport]').hasClass('disabled')) {
                return;
            }

            this.confirm(this.translate('importNow', 'messages', 'ImportFeed'), () => {
                const data = {
                    importFeedId: this.model.get('id'),
                    attachmentId: this.model.get('fileId')
                };
                this.ajaxPostRequest('ImportFeed/action/runImport', data).then(response => {
                    if (response) {
                        this.notify(this.translate('importRunning', 'labels', 'ImportFeed'), 'success');
                        this.model.trigger('importRun');
                    }
                });
            });
        },

        actionUploadAndRunImport() {
            if ($('.action[data-action=uploadAndRunImport]').hasClass('disabled')) {
                return;
            }

            this.createView('dialog', 'import:views/import-feed/modals/run-import-options', {
                model: this.model
            }, view => view.render());
        },

    })
);