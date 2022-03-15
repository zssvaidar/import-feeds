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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

Espo.define('import:views/import-feed/fields/unused-columns', 'views/fields/multi-enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'fileUpdate change:fileFieldDelimiter change:fileTextQualifier change:isFileHeaderRow', () => {
                this.loadFileColumns();
            });

            this.loadUnusedColumns();
            this.listenTo(this.model, 'change:allColumns after:relate after:save', () => {
                this.loadUnusedColumns();
            });

            this.listenTo(this.model, 'updateUnusedColumns', () => {
                setTimeout(() => this.loadUnusedColumns(), 1000);
            });
        },

        loadUnusedColumns() {
            const allColumns = this.model.get('allColumns') || [];

            if (!this.model.get('id')) {
                this.model.set('unusedColumns', allColumns);
                this.reRender();
                return;
            }

            this.ajaxGetRequest(`ImportFeed/${this.model.get('id')}/configuratorItems`).success(response => {
                let usedColumns = [];
                (response.list || []).forEach(item => {
                    (item.column || []).forEach(column => {
                        usedColumns.push(column);
                    });
                });

                let unusedColumns = [];

                allColumns.forEach(column => {
                    if (!usedColumns.includes(column)) {
                        unusedColumns.push(column);
                    }
                })

                this.model.set('unusedColumns', unusedColumns);
                this.reRender();
            });
        },

        loadFileColumns() {
            let fileId = this.model.get('fileId');
            if (!fileId) {
                return;
            }

            let data = {
                importFeedId: this.model.get('id'),
                delimiter: this.model.get('fileFieldDelimiter'),
                enclosure: this.model.get('fileTextQualifier'),
                isHeaderRow: this.model.get('isFileHeaderRow') ? 1 : 0
            };

            this.ajaxGetRequest(`ImportFeed/${fileId}/fileColumns`, data).success(response => {
                this.model.set('allColumns', response);
            });
        },

    })
);