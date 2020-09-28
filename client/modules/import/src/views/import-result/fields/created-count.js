

Espo.define('import:views/import-result/fields/created-count', 'import:views/fields/int-with-link-to-list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listScope = this.model.get('entityName');
        },

        getSearchFilter() {
            return {
                textFilter: '',
                primary: null,
                presetName: null,
                bool: {},
                advanced: {
                    'createdByImport-1': {
                        type: 'equals',
                        field: 'createdByImportId',
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
