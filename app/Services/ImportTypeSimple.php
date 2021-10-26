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
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Services\QueueManagerBase;
use Treo\Core\Exceptions\NotModified;

class ImportTypeSimple extends QueueManagerBase
{
    private array $services = [];
    private array $restore = [];

    public function run(array $data = []): bool
    {
        $importResult = $this->getEntityManager()->getEntity('ImportResult', $data['data']['importResultId']);
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

        // prepare file row
        $fileRow = (int)$data['offset'];

        foreach ($fileData as $row) {
            // increment file row number
            $fileRow++;

            $entity = $this->findExistEntity($this->getService($data['data']['entity'])->getEntityType(), $data['data'], $row);
            $id = !empty($entity) ? $entity->get('id') : null;

            if ($data['action'] == 'create' && !empty($entity)) {
                continue 1;
            }

            if ($data['action'] == 'update' && empty($entity)) {
                continue 1;
            }

            if (!$this->getEntityManager()->getPDO()->inTransaction()) {
                $this->getEntityManager()->getPDO()->beginTransaction();
            }

            try {
                $input = new \stdClass();
                $restore = new \stdClass();

                $attributes = [];
                foreach ($data['data']['configuration'] as $item) {
                    if ($item['type'] == 'Attribute') {
                        $attributes[] = ['item' => $item, 'row' => $row];
                        continue 1;
                    }

                    $type = $this->getMetadata()->get(['entityDefs', $item['entity'], 'fields', $item['name'], 'type'], 'varchar');
                    $this->getService('ImportConfiguratorItem')->getFieldConverter($type)->convert($input, $item, $row);
                    if (!empty($entity)) {
                        $this->getService('ImportConfiguratorItem')->getFieldConverter($type)->prepareValue($restore, $entity, $item);
                    }
                }

                $updatedEntity = null;
                if (empty($id)) {
                    $updatedEntity = $this->getService($data['data']['entity'])->createEntity($input);
                    $this->saveRestoreRow('created', $data['data']['entity'], $updatedEntity->get('id'));
                } else {
                    $updatedEntity = $this->getService($data['data']['entity'])->updateEntity($id, $input);
                    $this->saveRestoreRow('updated', $data['data']['entity'], [$id => $restore]);
                }

                foreach ($attributes as $attribute) {
                    $this->importAttribute($updatedEntity, $attribute);
                }

                if ($this->getEntityManager()->getPDO()->inTransaction()) {
                    $this->getEntityManager()->getPDO()->commit();
                }

            } catch (\Throwable $e) {
                if ($this->getEntityManager()->getPDO()->inTransaction()) {
                    $this->getEntityManager()->getPDO()->rollBack();
                }

                $message = empty($e->getMessage()) ? $this->getCodeMessage($e->getCode()) : $e->getMessage();

                // push log
                $this->log($data['data']['entity'], $data['data']['importResultId'], 'error', (string)$fileRow, $message);

                $updatedEntity = null;
            }

            if (!empty($updatedEntity)) {
                // prepare action
                $action = empty($id) ? 'create' : 'update';

                // push log
                $this->log($data['data']['entity'], $data['data']['importResultId'], $action, (string)$fileRow, $updatedEntity->get('id'));
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

        try {
            $this->getEntityManager()->saveEntity($log);
        } catch (\Throwable $e) {
            // ignore
        }

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

    protected function saveRestoreRow(string $action, string $entityType, $data): void
    {
        $this->restore[] = [
            'action' => $action,
            'entity' => $entityType,
            'data'   => $data
        ];
    }

    protected function getCodeMessage(int $code): string
    {
        if ($code == 304) {
            return $this->translate('nothingToUpdate', 'exceptions', 'ImportFeed');
        }

        if ($code == 403) {
            return $this->translate('permissionDenied', 'exceptions', 'ImportFeed');
        }

        return 'HTTP Code: ' . $code;
    }

    protected function importAttribute(Entity $product, array $data)
    {
        $entityType = 'ProductAttributeValue';
        $service = $this->getService($entityType);

        $inputRow = new \stdClass();
        $restoreRow = new \stdClass();

        $conf = $data['item'];
        $row = $data['row'];

        $attribute = $this->getEntityManager()->getEntity('Attribute', $conf['attributeId']);
        if (empty($attribute)) {
            throw new BadRequest("No such Attribute '{$conf['attributeId']}'.");
        }
        $conf['attribute'] = $attribute;

        $conf['name'] = 'value';
        if ($conf['locale'] !== 'main') {
            $conf['name'] .= Util::toCamelCase(strtolower($conf['locale']), '_', true);
        }

        $pavWhere = [
            'productId'   => $product->get('id'),
            'attributeId' => $conf['attributeId'],
            'scope'       => $conf['scope'],
        ];

        if ($conf['scope'] === 'Channel') {
            $pavWhere['channelId'] = $conf['channelId'];
        }

        $converter = $this->getService('ImportConfiguratorItem')->getFieldConverter($attribute->get('type'));

        $pav = $this->getEntityManager()->getRepository($entityType)->where($pavWhere)->findOne();
        if (!empty($pav)) {
            $inputRow->id = $pav->get('id');
            $converter->prepareValue($restoreRow, $pav, $conf);
        }

        // convert attribute value
        $converter->convert($inputRow, $conf, $row);

        if (!isset($inputRow->id)) {
            $inputRow->productId = $product->get('id');
            $inputRow->attributeId = $conf['attributeId'];
            $inputRow->scope = $conf['scope'];
            if ($conf['scope'] === 'Channel') {
                $inputRow->channelId = $conf['channelId'];
                $inputRow->channelName = $conf['channelId'];
            }

            $pavEntity = $service->createEntity($inputRow);
            $this->saveRestoreRow('created', $entityType, $pavEntity->get('id'));
        } else {
            $id = $inputRow->id;
            unset($inputRow->id);

            try {
                $service->updateEntity($id, $inputRow);
                $this->saveRestoreRow('updated', $entityType, [$id => $restoreRow]);
            } catch (NotModified $e) {
                // ignore
            }
        }
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
