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

namespace Import\Services;

use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

class ImportJob extends Base
{
    public function getImportJobsViaScope(string $scope): array
    {
        $logs = $this
            ->getEntityManager()
            ->getRepository('ImportJobLog')
            ->select(['importJobId', 'importJobName'])
            ->where(['entityName' => $scope])
            ->find();

        $result = [];
        foreach ($logs as $log) {
            $result[$log->get('importJobId')] = [
                'id'   => $log->get('importJobId'),
                'name' => $log->get('importJobName'),
            ];
        }

        return array_values($result);
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        // prepare id
        $id = (string)$entity->get('id');

        $entity->set('createdCount', $this->getLogCount('create', $id));
        $entity->set('updatedCount', $this->getLogCount('update', $id));
        $entity->set('deletedCount', $this->getLogCount('delete', $id));
        $entity->set('errorsCount', $this->getLogCount('error', $id));
    }

    protected function getLogCount(string $type, string $importJobId): int
    {
        return $this
            ->getEntityManager()
            ->getRepository('ImportJobLog')
            ->where(['importJobId' => $importJobId, 'type' => $type])
            ->count();
    }
}
