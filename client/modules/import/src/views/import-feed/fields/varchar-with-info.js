

Espo.define('import:views/import-feed/fields/varchar-with-info', 'views/fields/varchar',
    Dep => Dep.extend({

        detailTemplate: 'import:import-feed/fields/varchar-with-info/detail',

        listTemplate: 'import:import-feed/fields/varchar-with-info/detail',

        data() {
            let data = Dep.prototype.data.call(this);
            data.isRequired = !!this.getMetadata().get(['entityDefs', this.model.get('entity'), 'fields', this.model.get('name'), 'required']);
            data.extraInfo = this.getExtraInfo();
            return data;
        },

        getExtraInfo() {
            let extraInfo = null;

            let fieldName = (this.model.get('customData') || {}).fieldName;
            if (this.model.get('isLink') || this.model.get('isLinkMultiple') && fieldName) {
                extraInfo = `
                    <span class="text-muted small">
                        ${this.translate('importBy', 'labels', 'ImportFeed')}: ${fieldName}
                    </span>`;
            }

            if (this.model.get('attributeId')) {
                extraInfo = `
                    <span class="text-muted small">
                        ${this.translate('Attribute', 'scopeNames', 'Global')}
                    </span>`;
            }

            if (this.model.get('scope')) {
                extraInfo += `
                    <br>
                    <span class="text-muted small">
                        ${this.translate('scope', 'fields')}: ${this.model.get('scope')}
                    </span>`;
            }

            return extraInfo;
        },

        getValueForDisplay() {
            let name = this.translate(this.model.get(this.name), 'fields', this.model.get('entity'));
            if (this.model.get('attributeId') && this.model.get('locale')) {
                name += ' > ' + this.model.get('locale');
            }
            if (this.model.get('pimImage')) {
                name = this.translate('productImage', 'labels', 'Product');
            }
            return name;
        }

    })
);