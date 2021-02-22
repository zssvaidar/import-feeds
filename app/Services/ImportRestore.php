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

namespace Import\Services;

use Espo\Services\Record;
use Treo\Core\ServiceFactory;
use Treo\Services\AbstractService;
use Treo\Services\QueueManagerServiceInterface;

/**
 * Class ImportRestore
 */
class ImportRestore extends AbstractService implements QueueManagerServiceInterface
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * @param array $data
     *
     * @return bool
     */
    public function run(array $data = []): bool
    {
        if (!isset($data['importResultId']) || !isset($data['offset']) || !isset($data['limit'])) {
            return false;
        }

        // get importResult logs
        $logs = $this
            ->getEntityManager()
            ->getRepository('ImportResultLog')
            ->select(['restoreData'])
            ->where([
                'importResultId' => $data['importResultId'],
                'type' => ['create', 'update']
            ])
            ->order('rowNumber', 'DESC')
            ->limit($data['offset'], $data['limit'])
            ->find()
            ->toArray();

        foreach ($logs as $log) {
            foreach ($log['restoreData'] as $item) {
                $service = $this->getService($item->entity);

                if ($item->action == 'created' && is_string($item->data)) {
                    // remove created entity
                    try {
                        $service->deleteEntity($item->data);
                    } catch (\Throwable $e) {
                    }
                } elseif ($item->action == 'updated' && is_object($item->data)) {
                    $id = array_keys((array)$item->data)[0];
                    try {
                        $service->updateEntity($id, $item->data->{$id});
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param string $name
     *
     * @return Record
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function getService(string $name): Record
    {
        $service = null;

        if (isset($this->services[$name])) {
            $service = $this->services[$name];
        } else {
            $service = $this->getServiceFactory()->create($name);
            $this->services[$name] = $service;
        }

        return $service;
    }

    /**
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->getContainer()->get('serviceFactory');
    }
}
