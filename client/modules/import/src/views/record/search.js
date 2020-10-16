

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

Espo.define('import:views/record/search', 'class-replace!import:views/record/search', Dep => Dep.extend({

    createFilters(callback) {
        this.modifySearchModel();
        Dep.prototype.createFilters.call(this, callback);
    },

    modifySearchModel() {
        const additionalDefs = [
            {
                name: 'createdByImport',
                field: {
                    type: 'link',
                    readOnly: true
                },
                link: {
                    entity: 'ImportResult',
                    type: "belongsTo"
                }
            },
            {
                name: 'updatedByImport',
                field: {
                    type: 'link',
                    readOnly: true
                },
                link: {
                    entity: 'ImportResult',
                    type: "belongsTo"
                }
            }
        ];

        additionalDefs.forEach(item => {
            if (!(item.name in this.model.defs.fields)) {
                this.model.defs.fields[item.name] = item.field;
            }
            if (!(item.name in this.model.defs.links)) {
                this.model.defs.links[item.name] = item.link;
            }
        });
    }

}));
