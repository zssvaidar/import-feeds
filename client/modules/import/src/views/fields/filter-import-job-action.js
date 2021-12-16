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

Espo.define('import:views/fields/filter-import-job-action', 'views/fields/enum',
    Dep => Dep.extend({

        setup() {
            this.params.options = ['create', 'update'];
            this.translatedOptions = {
                "create": this.translate('createdCount', 'fields', 'ImportJob'),
                "update": this.translate('updatedCount', 'fields', 'ImportJob')
            };

            Dep.prototype.setup.call(this);
        },

    })
);