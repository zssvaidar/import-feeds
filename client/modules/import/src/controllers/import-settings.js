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

Espo.define('import:controllers/import-settings', ['controllers/admin', 'models/settings'],
    (Dep, Settings) => Dep.extend({

        importSettings() {
            let model = this.getSettingsModel();

            model.once('sync', () => {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'import:admin/import-settings/headers/import-settings',
                    recordView: 'import:views/admin/import-settings'
                });
            }, this);
            model.fetch();
        },

        getSettingsModel() {
            let model = new Settings(null);
            model.defs = this.getMetadata().get('entityDefs.Settings');
            return model;
        }

    })
);