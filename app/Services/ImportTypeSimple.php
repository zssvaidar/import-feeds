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
use Espo\Core\Services\Base;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\Services\QueueManagerBase;

/**
 * Class ImportTypeSimple
 */
class ImportTypeSimple extends QueueManagerBase
{
    public function run(array $data = []): bool
    {
        $importResult = $this->getEntityManager()->getEntity('ImportResult', $data['data']['importResultId']);
        if (empty($importResult)) {
            throw new BadRequest('No such ImportResult.');
        }

        $importFeed = $importResult->get('importFeed');
        if (empty($importResult)) {
            throw new BadRequest('No such ImportFeed.');
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment', $data['attachmentId']);
        if (empty($attachment)) {
            throw new BadRequest('No such Attachment.');
        }

        $fileData = $this->getService('CsvFileParser')->getFileData($attachment, $data['delimiter'], $data['enclosure'], $data['offset'], $data['limit']);
        if (empty($fileData)) {
            throw new BadRequest('File is empty.');
        }

        $entityService = $this->getService($data['data']['entity']);

        // prepare file row
        $fileRow = (int)$data['offset'];

        foreach ($fileData as $row) {
            // increment file row number
            $fileRow++;

            $entity = null;
            $id = null;

            if ($data['action'] == 'update') {
                $entity = $this->findExistEntity($entityService->getEntityType(), $data['data'], $row);
                if (empty($entity)) {
                    continue 1;
                }
                $id = $entity->get('id');
            }

            if ($data['action'] == 'create_update') {
                $entity = $this->findExistEntity($entityService->getEntityType(), $data['data'], $row);
                $id = empty($entity) ? null : $entity->get('id');
            }

            echo '<pre>';
            print_r($entity->toArray());
            die();

            try {
                // begin transaction
                $this->getEntityManager()->getPDO()->beginTransaction();

                // prepare row and data for restore
                $input = new \stdClass();
                $restore = new \stdClass();

                foreach ($data['data']['configuration'] as $item) {

                    $this->convertItem($input, $entityType, $item, $row, $data['data']['delimiter']);

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

                $this->getEntityManager()->getPDO()->commit();
            } catch (\Throwable $e) {
                // roll back transaction
                $this->getEntityManager()->getPDO()->rollBack();

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

        echo '<pre>';
        print_r($fileData);
        die();

        return true;
    }

    protected function getService(string $name): Base
    {
        return $this->getContainer()->get('serviceFactory')->create($name);
    }

    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }

    protected function findExistEntity(string $entityType, array $configuration, array $row): ?Entity
    {
        $service = $this->getService('ImportConfiguratorItem');

        $where = [];
        foreach ($configuration['configuration'] as $item) {
            if (in_array($item['name'], $configuration['idField'])) {
                $type = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item['name'], 'type'], 'varchar');
                if (!empty($converter = $service->getFieldConverter($type))) {
                    $converter->prepareFindExistEntityWhere($where, $item, $row);
                    continue 1;
                }

                $value = $item['default'];

                if (isset($item['column'][0]) && isset($row[$item['column'][0]])) {
                    $value = $row[$item['column'][0]];
                }

                $where[$item['name']] = $value;
            }
        }

        echo '<pre>';
        print_r($where);
        die();

        if (empty($where)) {
            return null;
        }

        return $this->getEntityManager()->getRepository($entityType)->where($where)->findOne();
    }
}
