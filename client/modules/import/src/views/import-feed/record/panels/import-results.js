

Espo.define('import:views/import-feed/record/panels/import-results', 'views/record/panels/relationship',
    Dep => Dep.extend({

        refreshIntervalGap: 5000,

        refreshInterval: null,

        pauseRefreshInterval: false,

        setup() {
            Dep.prototype.setup.call(this);

            this.listenToOnce(this, 'after:render', () => {
                if (this.collection) {
                    this.refreshInterval = window.setInterval(() => {
                        if (!this.pauseRefreshInterval) {
                            this.actionRefresh();
                        }
                    }, this.refreshIntervalGap);

                    this.listenTo(this.collection, 'pauseRefreshInterval', value => {
                        this.pauseRefreshInterval = value;
                    });
                }
            });

            this.listenToOnce(this, 'remove', () => {
                if (this.refreshInterval) {
                    window.clearInterval(this.refreshInterval);
                }
            });

            this.listenTo(this.model, 'importRun', () => {
                this.actionRefresh();
            });
        }

    })
);
