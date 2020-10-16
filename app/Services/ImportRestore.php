<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Import\Services;

use Espo\Core\Utils\Json;
use Espo\Services\Record;
use Treo\Core\ServiceFactory;
use Treo\Services\QueueManagerBase;

/**
 * Class ImportRestore
 */
class ImportRestore extends QueueManagerBase
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
            ->order('createdAt', 'DESC')
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