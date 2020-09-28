

Espo.define('import:views/admin/fields/max-import-job-in-history', 'views/fields/int',
    Dep => Dep.extend({

        editTemplate: 'import:admin/import-settings/fields/max-import-job-in-history/edit',

        data: function () {
            var data = Dep.prototype.data.call(this);

            data.defaultMaxImportJobInHistory = 20;

            return data;
        }
    })
);