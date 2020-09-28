

Espo.define('import:views/import-feed/modals/array-entity-add', 'views/modals/array-field-add',
    Dep => Dep.extend({

        template: 'import:import-feed/modals/array-entity-add',

        optionList: [],

        data: function () {
            return {
                optionList: this.getOptionList(),
                translatedOptions: this.options.translatedOptions
            };

        },

        events: {
            'click button[data-action="add"]': function (e) {
                let value = $(e.currentTarget).attr('data-value');
                let data = {
                    value: value
                };
                let field = $(e.currentTarget).prev().find('select').val();
                if (field) {
                    let option = this.optionList.find(item => item.value === value) || {};
                    let translates = option.translatedOptions || {};
                    data.foreign = option.foreign;
                    data.field = field;
                    data.fieldName = translates[field];
                }
                this.trigger('add', data);
            },
        },

        getOptionList() {
            this.optionList = [];
            let fields = this.getEntityFields(this.options.scope);
            (this.options.options || []).forEach(option => {
                let data = {
                    value: option
                };
                if (fields[option].type === 'link' || fields[option].type === 'linkMultiple') {
                    let foreign = this.getMetadata().get(['entityDefs', this.options.scope, 'links', option, 'entity']) || {};
                    let filteredFields = this.getEntityFields(foreign, true);
                    let options = Object.keys(filteredFields);
                    if (options.length) {
                        data.foreign = foreign;
                        data.relation = true;
                        data.options = ['id'].concat(options);
                        let translatedOptions = {id: this.getLanguage().translate('id', 'fields', 'Global')};
                        options.forEach(item => translatedOptions[item] = this.getLanguage().translate(item, 'fields', foreign));
                        data.translatedOptions = translatedOptions;
                    }
                }
                this.optionList.push(data);
            });
            return this.optionList;
        },

        getEntityFields(entity, filter) {
            let fields = {};
            if (entity) {
                fields = this.getMetadata().get(['entityDefs', entity, 'fields']) || {};
                if (filter) {
                    let result = {};
                    let availableTypes = [
                        'varchar'
                    ];
                    Object.keys(fields).forEach(name => {
                        let field = fields[name];
                        if (
                            !field.customizationDisabled
                            &&
                            !field.disabled
                            &&
                            !field.notStorable
                            &&
                            !!field.type
                            &&
                            availableTypes.includes(field.type)
                            &&
                            (name === 'code' || !field.emHidden)
                            &&
                            !field.importDisabled
                        ) {
                            result[name] = field;
                        }
                    });
                    fields = result;
                }
            }
            return fields;
        },

    })
);

