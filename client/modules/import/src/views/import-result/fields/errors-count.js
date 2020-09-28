

Espo.define('import:views/import-result/fields/errors-count', 'import:views/fields/int-with-link-to-list',
    Dep => Dep.extend({

        listScope: 'ImportResultLog',

        getSearchFilter() {
            return {
                textFilter: '',
                primary: null,
                presetName: null,
                bool: {},
                advanced: {
                    'importResult-1': {
                        type: 'equals',
                        field: 'importResultId',
                        value: this.model.id,
                        data: {
                            type: 'is',
                            idValue:  this.model.id,
                            nameValue: this.model.get('name')
                        }
                    }
                }
            };
        }

    })
);
