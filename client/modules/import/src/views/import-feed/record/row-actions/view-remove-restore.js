

Espo.define('import:views/import-feed/record/row-actions/view-remove-restore', 'views/record/row-actions/view-and-remove', function (Dep) {

    return Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            const first = this.model.collection.models[0];
            this.model.isRestorable = !!(first && first.id === this.model.id);
        },

        getActionList: function () {
            const actionList = Dep.prototype.getActionList.call(this);

            if (this.model.isRestorable) {
                actionList.push({
                    action: 'restore',
                    label: 'Restore'
                });
            }

            return actionList;
        },

        actionRestore: function () {
            this.confirm({
                message: this.translate('Do you really want to restore data? Old values will be deleted.', 'messages', 'ImportResult'),
                confirmText: 'Restore'
            }, function () {
                $.ajax({
                    url: `ImportResult/action/restore`,
                    type: 'POST',
                    data: JSON.stringify({
                        id: this.model.id
                    }),
                    contentType: 'application/json',
                    success: function () {
                        setTimeout(() => {
                            Backbone.trigger('showQueuePanel');
                        }, 2000);
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        }
    });
});
