

Espo.define('import:views/fields/entity-type', 'views/fields/entity-type',
    Dep => Dep.extend({

        checkAvailability: function (entityType) {
            let defs = this.scopesMetadataDefs[entityType] || {};
                if (defs.entity && !defs.disabled) {
                    return true;
                }
        },

        setupOptions: function () {
            Dep.prototype.setupOptions.call(this);
            this.params.options.shift();
        },

    })
);
