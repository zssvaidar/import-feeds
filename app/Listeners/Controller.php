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
 *
 * This software is not allowed to be used in Russia and Belarus.
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
        $scope = $event->getArgument('controller');

        // get request
        $request = $event->getArgument('request');
        if (!empty($where = $request->get('where'))) {
            foreach ($where as $k => $item) {
                if (!empty($newItem = $this->prepareImportJobFilter($scope, $item))) {
                    $where[$k] = $newItem;
                }
            }
            $request->setQuery('where', array_values($where));
        }
    }

    protected function prepareImportJobFilter(string $scope, array $item): array
    {
        if (isset($item['attribute']) && $item['attribute'] === 'filterImportJob') {
            return [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName'  => $scope,
                    'type'        => [
                        'create',
                        'update'
                    ],
                    'importJobId' => (array)$item['value']
                ])
            ];
        }

        if (!empty($item['value'][1]['type']) && $item['value'][1]['type'] === 'notIn' && $item['value'][1]['attribute'] === 'filterImportJob') {
            return [
                'type'      => 'notIn',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName'  => $scope,
                    'type'        => [
                        'create',
                        'update'
                    ],
                    'importJobId' => (array)$item['value'][1]['value']
                ])
            ];
        }

        if (!empty($item['value'][1]['type']) && $item['value'][1]['type'] === 'equals' && $item['value'][1]['attribute'] === 'filterImportJob') {
            return [
                'type'      => 'notIn',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName' => $scope,
                    'type'       => [
                        'create',
                        'update'
                    ]
                ])
            ];
        }

        if (!empty($item['value'][1]['type']) && $item['value'][1]['type'] === 'notEquals' && $item['value'][1]['attribute'] === 'filterImportJob') {
            return [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName' => $scope,
                    'type'       => [
                        'create',
                        'update'
                    ]
                ])
            ];
        }

        if (isset($item['attribute']) && $item['attribute'] === 'filterImportJobAction') {
            return [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName' => $scope,
                    'type'       => (array)$item['value']
                ])
            ];
        }

        if (!empty($item['value'][1]['type']) && $item['value'][1]['type'] === 'notIn' && $item['value'][1]['attribute'] === 'filterImportJobAction') {
            return [
                'type'      => 'notIn',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName' => $scope,
                    'type'       => (array)$item['value'][1]['value']
                ])
            ];
        }

        if (!empty($item['value'][1]['type']) && $item['value'][1]['type'] === 'equals' && $item['value'][1]['attribute'] === 'filterImportJobAction') {
            return [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => ['no-such-id']
            ];
        }

        if (!empty($item['value'][1]['type']) && $item['value'][1]['type'] === 'notEquals' && $item['value'][1]['attribute'] === 'filterImportJobAction') {
            return [
                'type'      => 'in',
                'attribute' => 'id',
                'value'     => $this->getEntitiesIds([
                    'entityName' => $scope,
                    'type'       => [
                        'create',
                        'update'
                    ]
                ])
            ];
        }

        return [];
    }

    protected function getEntitiesIds(array $where): array
    {
        return array_column($this->getEntityManager()->getRepository('ImportJobLog')->select(['entityId'])->where($where)->find()->toArray(), 'entityId');
    }

    protected function getIdsViaJobType(string $scope, array $types): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('ImportJobLog')
            ->select(['entityId'])
            ->where(
                [
                    'entityName' => $scope,
                    'type'       => $types
                ]
            )
            ->find();

        return array_column($data->toArray(), 'entityId');
    }
}
