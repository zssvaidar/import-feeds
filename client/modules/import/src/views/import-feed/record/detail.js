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

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'after:save', () => {
                this.handleButtonDisability();
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            const $btnGroup = this.$el.find('.detail-button-container .btn-group.pull-left');
            $btnGroup.css('min-width', '275px');

            $btnGroup.append(`<button type="button" class="btn btn-default action disabled" data-action="runImport">${this.translate('import', 'labels', 'ImportFeed')}</button>`);
            $btnGroup.append(`<button type="button" class="btn btn-default action disabled" data-action="uploadAndRunImport">${this.translate('uploadAndImport', 'labels', 'ImportFeed')}</button>`);

            setTimeout(() => this.handleButtonDisability(), 100);
        },

        handleButtonDisability() {
            const $runImport = this.$el.find('button[data-action="runImport"]');
            const $uploadAndRunImport = this.$el.find('button[data-action="uploadAndRunImport"]');

            if (this.model.get('isActive') && this.model.get('fileId')) {
                $runImport.removeClass('disabled');
                $uploadAndRunImport.removeClass('disabled');
            } else {
                $runImport.addClass('disabled');
                $uploadAndRunImport.addClass('disabled');
            }
        },

        actionRunImport() {
            this.confirm(this.translate('importNow', 'messages', 'ImportFeed'), () => {
                const data = {
                    importFeedId: this.model.id,
                    attachmentId: null
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
            this.createView('dialog', 'import:views/import-feed/modals/run-import-options', {
                model: this.model
            }, view => view.render());
        },

    })
);