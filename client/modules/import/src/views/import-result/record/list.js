

Espo.define('import:views/import-result/record/list', 'views/record/list',
    Dep => Dep.extend({

        rowActionsView: 'views/record/row-actions/view-and-remove',

        getSelectAttributeList: function (callback) {
            Dep.prototype.getSelectAttributeList.call(this, attributeList => {
                if (Array.isArray(attributeList) && !attributeList.includes('entityName')) {
                    attributeList.push('entityName', 'importFeedId');
                }
                callback(attributeList);
            });
        },

    })
);
