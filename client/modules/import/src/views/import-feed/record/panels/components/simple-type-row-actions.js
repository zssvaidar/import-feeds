

Espo.define('import:views/import-feed/record/panels/components/simple-type-row-actions', 'views/record/row-actions/default',
    Dep => Dep.extend({

        getActionList() {
            let list = [];
            if (this.options.acl.edit) {
                list.push({
                    action: 'quickEdit',
                    label: 'Edit',
                    data: {
                        id: this.model.id
                    },
                    link: '#' + this.model.name + '/edit/' + this.model.id
                });
            }

            if (this.options.acl.delete && !this.model.getFieldParam('default', 'required')) {
                list.push({
                    action: 'quickRemove',
                    label: 'Remove',
                    data: {
                        id: this.model.id
                    }
                });
            }
            return list;
        }

    })
);