

Espo.define('import:views/import-feed/fields/file', 'views/fields/file',
    Dep => Dep.extend({

        editTemplate: 'import:import-feed/fields/file/edit',

        accept: ['.csv'],

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:fileId', () => {
                this.model.trigger('fileUpdate');
            });
        }
    })
);