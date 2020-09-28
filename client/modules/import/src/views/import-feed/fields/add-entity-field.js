

Espo.define('import:views/import-feed/fields/add-entity-field', 'view',
    Dep => Dep.extend({

        template: 'import:import-feed/fields/add-entity-field/base',

        fields: [],

        events: {
            'click button[data-action="actionAddEntityField"]': function () {
                this.actionAddField();
            }
        },

        data() {
            return {
                name: this.name,
                label: this.options.label
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.fields = Object.keys(this.options.fields || {});
        },

        actionAddField() {
            if (this.getParentView().mode === 'edit') {
                let options = this.fields.filter(field => !this.options.selectedFields.includes(field));
                let translatedOptions = {};
                options.forEach(field => {
                    let label = this.translate(field, 'fields', this.options.entity);
                    let type = (this.options.fields || {})[field].type;
                    if (type) {
                        type = this.translate(type, 'fieldTypes', 'Admin');
                        label = `${label} (${type})`;
                    }
                    translatedOptions[field] = label;
                });

                this.createView('addModal', 'import:views/import-feed/modals/array-entity-add', {
                    scope: this.options.entity,
                    options: options,
                    translatedOptions: translatedOptions
                }, view => {
                    view.render();
                    this.listenTo(view, 'add', data => {
                        if (!this.options.selectedFields.includes(data.value)) {
                            this.options.selectedFields.push(data.value);
                            view.options.options = (this.fields || []).filter(item => !this.options.selectedFields.includes(item));
                            this.trigger('addField', data);
                            view.reRender();
                        }
                    }, this);
                });
            }
        }
    })
);