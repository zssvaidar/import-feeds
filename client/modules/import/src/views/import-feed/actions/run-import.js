

Espo.define('import:views/import-feed/actions/run-import', 'view',
    Dep => Dep.extend({

        _template: '',

        runImport() {
            this.createView('dialog', 'import:views/import-feed/modals/run-import-options', {
                model: this.model
            }, view => view.render());
        },
    })
);
