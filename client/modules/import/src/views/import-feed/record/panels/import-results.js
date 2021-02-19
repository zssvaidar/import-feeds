

/*
 * Import Feeds
 * Free Extension
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
