

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

Espo.define('import:views/fields/int-with-link-to-list', 'views/fields/int',
    Dep => Dep.extend({

        listTemplate: 'import:fields/int-with-link-to-list/list',

        detailTemplate: 'import:fields/int-with-link-to-list/detail',

        listScope: '',

        events: _.extend({
            'click [data-action="showList"]': function (e) {
                e.preventDefault();
                e.stopPropagation();
                this.actionShowList();
            }
        }, Dep.prototype.events),

        actionShowList() {
            const searchFilter = this.getSearchFilter();
            this.getStorage().set('listSearch', this.listScope, searchFilter);
            window.open(`#${this.listScope}`, '_blank');
        },

        getSearchFilter() {
            return {};
        }

    })
);
