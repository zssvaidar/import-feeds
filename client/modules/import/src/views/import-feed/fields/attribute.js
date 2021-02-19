

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

Espo.define('import:views/import-feed/fields/attribute', 'treo-core:views/fields/filtered-link',
    Dep => Dep.extend({

        createDisabled: true,

        setup() {
            this.mandatorySelectAttributeList = ['type', 'typeValue', 'isMultilang'];

            Dep.prototype.setup.call(this);
        },

        select(model) {
            this.setAttributeFieldsToModel(model);

            Dep.prototype.select.call(this, model);
        },

        setAttributeFieldsToModel(model) {
            let attributes = {
                name: model.get('attributeName'),
                type: model.get('type'),
                options: model.get('typeValue'),
                isMultilang: model.get('isMultilang'),
            };
            (this.typeValueFields || []).forEach(item => attributes[item] = model.get(item));
            this.model.set(attributes);
        }

    })
);

