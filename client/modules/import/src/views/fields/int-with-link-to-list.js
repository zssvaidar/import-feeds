

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
