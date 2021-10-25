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

Espo.define('import:views/import-feed/fields/unused-columns', 'views/fields/multi-enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.loadFileColumns();

            this.listenTo(this.model, 'fileUpdate change:fileFieldDelimiter change:fileTextQualifier change:isFileHeaderRow after:relate', () => {
                this.loadFileColumns();
            });

            this.listenTo(this.model, 'configurator-item-removed', () => {
                setTimeout(() => this.loadFileColumns(), 1000);
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
                let columns = [];
                response.forEach(row => {
                    if (!row.isUsed) {
                        columns.push(row.name);
                    }
                });

                localStorage.setItem('importAllColumns', response.map(row => {
                    return row.name
                }).join(','))
                this.model.set('unusedColumns', columns);
                this.reRender();
            });
        },

    })
);