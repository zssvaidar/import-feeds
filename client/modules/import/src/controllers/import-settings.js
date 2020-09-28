

Espo.define('import:controllers/import-settings', ['controllers/admin', 'models/settings'],
    (Dep, Settings) => Dep.extend({

        importSettings() {
            let model = this.getSettingsModel();

            model.once('sync', () => {
                model.id = '1';
                this.main('views/settings/edit', {
                    model: model,
                    headerTemplate: 'import:admin/import-settings/headers/import-settings',
                    recordView: 'import:views/admin/import-settings'
                });
            }, this);
            model.fetch();
        },

        getSettingsModel() {
            let model = new Settings(null);
            model.defs = this.getMetadata().get('entityDefs.Settings');
            return model;
        }

    })
);