

Espo.define('import:views/admin/import-settings', 'views/settings/record/edit',
    Dep => Dep.extend({

        layoutName: null,

        detailLayout: [
            {
                "label": "importSettings",
                "rows": [
                    [
                        {
                            "name": "maxImportJobInHistory",
                        },
                        false
                    ]
                ]
            }
        ]
    })
);
