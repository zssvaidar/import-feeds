

Espo.define('import:views/import-feed/fields/attribute', 'treo-core:views/fields/filtered-link',
    Dep => Dep.extend({

        createDisabled: true,

        setup() {
            this.mandatorySelectAttributeList = ['type', 'typeValue', 'isMultilang'];

            Dep.prototype.setup.call(this);
        },

        select(model) {
            this.setAttributeFieldsToModel(model);

            Dep.prototype.select.call(this, model);
        },

        setAttributeFieldsToModel(model) {
            let attributes = {
                name: model.get('attributeName'),
                type: model.get('type'),
                options: model.get('typeValue'),
                isMultilang: model.get('isMultilang'),
            };
            (this.typeValueFields || []).forEach(item => attributes[item] = model.get(item));
            this.model.set(attributes);
        }

    })
);

