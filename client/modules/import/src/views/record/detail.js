

Espo.define('import:views/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        setup() {
            this.bottomView = this.getMetadata().get(`clientDefs.${this.scope}.bottomView.${this.type}`) || this.bottomView;

            Dep.prototype.setup.call(this);

            if (this.options.setEditMode) {
                this.listenToOnce(this, 'after:render', () => this.actionEdit());
            }
        },

    })
);