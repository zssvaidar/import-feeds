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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Services\Base;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\Services\QueueManagerBase;

class ImportTypeSimple extends QueueManagerBase
{
    private array $services = [];
    private array $restore = [];

    public function run(array $data = []): bool
    {
        $importResultId = $data['data']['importResultId'];

        $importResult = $this->getEntityManager()->getEntity('ImportResult', $importResultId);
        if (empty($importResult)) {
            throw new BadRequest('No such ImportResult.');
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment', $data['attachmentId']);
        if (empty($attachment)) {
            throw new BadRequest('No such Attachment.');
        }

        $fileData = $this->getService('CsvFileParser')->getFileData($attachment, $data['delimiter'], $data['enclosure'], $data['offset'], $data['limit']);
        if (empty($fileData)) {
            throw new BadRequest('File is empty.');
        }

        $entityType = $data['data']['entity'];

        $service = $this->getService($entityType);

        // prepare file row
        $fileRow = (int)$data['offset'];

        foreach ($fileData as $row) {
            // increment file row number
            $fileRow++;

            $entity = null;
            $id = null;

            if ($data['action'] == 'update') {
                $entity = $this->findExistEntity($service->getEntityType(), $data['data'], $row);
                if (empty($entity)) {
                    continue 1;
                }
                $id = $entity->get('id');
            }

            if ($data['action'] == 'create_update') {
                $entity = $this->findExistEntity($service->getEntityType(), $data['data'], $row);
                $id = empty($entity) ? null : $entity->get('id');
            }

            if (!$this->getEntityManager()->getPDO()->inTransaction()) {
                $this->getEntityManager()->getPDO()->beginTransaction();
            }

            try {
                $input = new \stdClass();
                $restore = new \stdClass();

                foreach ($data['data']['configuration'] as $item) {
                    $this->convertItem($input, $item, $row);
                    if (!empty($entity)) {
                        $this->prepareValue($restore, $entity, $item);
                    }
                }

                $updatedEntity = null;
                if (empty($id)) {
                    $updatedEntity = $service->createEntity($input);
                    $this->saveRestoreRow('created', $entityType, $updatedEntity->get('id'));
                } else {
                    $updatedEntity = $service->updateEntity($id, $input);
                    $this->saveRestoreRow('updated', $entityType, [$id => $restore]);
                }

                if ($this->getEntityManager()->getPDO()->inTransaction()) {
                    $this->getEntityManager()->getPDO()->commit();
                }

            } catch (\Throwable $e) {
                if ($this->getEntityManager()->getPDO()->inTransaction()) {
                    $this->getEntityManager()->getPDO()->rollBack();
                }

                // prepare message
                $message = $e->getMessage();
                if (get_class($e) == Forbidden::class && empty($message)) {
                    $message = 'Permission denied';
                }

                // push log
                $this->log($entityType, $importResultId, 'error', (string)$fileRow, $message);

                $updatedEntity = null;
            }

            if (!empty($updatedEntity)) {
                // prepare action
                $action = empty($id) ? 'create' : 'update';

                // push log
                $this->log($entityType, $importResultId, $action, (string)$fileRow, $updatedEntity->get('id'));
            }
        }

        return true;
    }

    public function log(string $entityName, string $importResultId, string $type, string $row, string $data): Entity
    {
        // create log
        $log = $this->getEntityManager()->getEntity('ImportResultLog');
        $log->set('name', $row);
        $log->set('rowNumber', $row);
        $log->set('entityName', $entityName);
        $log->set('importResultId', $importResultId);
        $log->set('type', $type);
        if ($type == 'error') {
            $log->set('message', $data);
        } else {
            $log->set('entityId', $data);
            $log->set('restoreData', $this->restore);
        }

        $this->getEntityManager()->saveEntity($log);

        $this->restore = [];

        return $log;
    }

    protected function findExistEntity(string $entityType, array $configuration, array $row): ?Entity
    {
        $where = [];
        foreach ($configuration['configuration'] as $item) {
            if (in_array($item['name'], $configuration['idField'])) {
                $this
                    ->getService('ImportConfiguratorItem')
                    ->getFieldConverter($this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item['name'], 'type'], 'varchar'))
                    ->prepareFindExistEntityWhere($where, $item, $row);
            }
        }

        if (empty($where)) {
            return null;
        }

        return $this->getEntityManager()->getRepository($entityType)->where($where)->findOne();
    }

    protected function convertItem(\stdClass $inputRow, array $item, array $row): void
    {
        if ($item['type'] == 'Attribute') {
            // @todo attributes
            return;
        }

        $type = $this->getMetadata()->get(['entityDefs', $item['entity'], 'fields', $item['name'], 'type'], 'varchar');

        $this->getService('ImportConfiguratorItem')->getFieldConverter($type)->convert($inputRow, $item, $row);
    }

    protected function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        if ($item['type'] == 'Attribute') {
            // @todo attributes
            return;
        }

        $type = $this->getMetadata()->get(['entityDefs', $item['entity'], 'fields', $item['name'], 'type'], 'varchar');

        $this->getService('ImportConfiguratorItem')->getFieldConverter($type)->prepareValue($restore, $entity, $item);
    }

    protected function saveRestoreRow(string $action, string $entityType, $data): void
    {
        $this->restore[] = [
            'action' => $action,
            'entity' => $entityType,
            'data'   => $data
        ];
    }

    protected function getService(string $name): Base
    {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->getContainer()->get('serviceFactory')->create($name);
        }

        return $this->services[$name];
    }

    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
}
