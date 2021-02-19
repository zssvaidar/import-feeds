

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

Espo.define('import:views/import-feed/fields/column', 'views/fields/enum',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.validations = Espo.Utils.clone(this.validations);
            if (!this.validations.includes('defaultValue')) {
                this.validations.push('defaultValue');
            }
        },

        validateDefaultValue() {
            let validate = false;
            let keys = this.getFieldManager().getActualAttributeList(this.model.getFieldType('default'), 'default').map(key => key);
            validate = !this.checkValueExists('column') && !keys.every(key => this.checkValueExists(key));
            if (validate) {
                let column = this.translate('column', 'fields', 'ImportFeed');
                let defaultValue = this.translate('default', 'fields', 'ImportFeed');
                let msg = this.translate('columnOrDefaultValueIsRequired', 'messages', 'ImportFeed')
                    .replace('{column}', column)
                    .replace('{default}', defaultValue);
                this.showValidationMessage(msg);
                this.trigger('invalid');
            }
            return validate;
        },

        checkValueExists(key) {
            return this.model.has(key) && typeof this.model.get(key) !== 'undefined' && this.model.get(key) !== null && this.model.get(key);
        }

    })
);