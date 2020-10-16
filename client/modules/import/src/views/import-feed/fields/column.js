

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