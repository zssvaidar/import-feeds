

Espo.define('import:views/list', 'views/list',
    Dep => Dep.extend({

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            Dep.prototype.setup.call(this);
        },

        navigateToEdit(id) {
            let router = this.getRouter();

            router.dispatch(this.scope, 'view', {
                id: id,
                setEditMode: true,
                optionsToPass: ['setEditMode']
            });
            router.navigate(`#${this.scope}/view/${id}`, {trigger: false});
        },

        actionQuickCreate() {
            let options = _.extend({
                scope: this.scope,
                attributes: this.getCreateAttributes() || {}
            }, this.getMetadata().get(`clientDefs.${this.scope}.quickCreateOptions`) || {})

            this.notify('Loading...');
            this.createView('quickCreate', 'views/modals/edit', options, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();

                    if (this.getMetadata().get(`clientDefs.${this.scope}.navigateToEntityAfterQuickCreate`)) {
                        this.navigateToEdit(view.getView('edit').model.id);
                    }
                }, this);
            }.bind(this));
        },

        afterRender: function () {
            if (!this.hasView('list')) {
                this.loadList();
            } else {
                this.collection.fetch();
            }
        },

    })
);

