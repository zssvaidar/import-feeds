<?php
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

declare(strict_types=1);

namespace Import\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class Controller
 */
class Controller extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeAction(Event $event)
    {
        // get controller
        $controller = $event->getArgument('controller');

        // get request
        $request = $event->getArgument('request');

        if (!empty($where = $request->get('where'))) {
            foreach ($where as $k => $item) {
                // for create type
                if (isset($item['attribute']) && $item['attribute'] == 'createdByImportId') {
                    $where[$k] = [
                        'type'      => 'in',
                        'attribute' => 'id',
                        'value'     => $this->getLogIds('create', $controller, $item['value'])
                    ];
                }

                // for update type
                if (isset($item['attribute']) && $item['attribute'] == 'updatedByImportId') {
                    $where[$k] = [
                        'type'      => 'in',
                        'attribute' => 'id',
                        'value'     => $this->getLogIds('update', $controller, $item['value'])
                    ];
                }
            }

            // set where
            $request->setQuery('where', $where);
        }
    }

    private function getLogIds(string $type, string $entityName, string $importJobId): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('ImportJobLog')
            ->select(['entityId'])
            ->where(
                [
                    'type'           => $type,
                    'entityName'     => $entityName,
                    'importJobId' => $importJobId
                ]
            )
            ->find()->toArray();

        return array_column($data, 'entityId');
    }
}
