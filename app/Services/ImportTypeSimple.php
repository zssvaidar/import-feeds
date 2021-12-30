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
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Services\Base;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\Services\QueueManagerBase;
use Import\Exceptions\IgnoreAttribute;
use Import\Entities\ImportFeed;
use Treo\Core\Exceptions\NotModified;
use Treo\Core\FilePathBuilder;

class ImportTypeSimple extends QueueManagerBase
{
    private array $services = [];
    private array $restore = [];
    private array $updatedPav = [];
    private array $deletedPav = [];
    private int $iterations = 0;

    public function prepareJobData(ImportFeed $feed, string $attachmentId): array
    {
        if (empty($attachmentId) || empty($file = $this->getEntityManager()->getEntity('Attachment', $attachmentId))) {
            throw new NotFound($this->translate('noSuchFile', 'exceptions', 'ImportFeed'));
        }

        if (!$this->isFileValid($feed, $file)) {
            throw new BadRequest($this->translate('theFileDoesNotMatchTheTemplate', 'exceptions', 'ImportFeed'));
        }

        return [
            "name"            => $feed->get('name'),
            "offset"          => $feed->isFileHeaderRow() ? 1 : 0,
            "limit"           => \PHP_INT_MAX,
            "delimiter"       => $feed->getDelimiter(),
            "enclosure"       => $feed->getEnclosure(),
            "isFileHeaderRow" => $feed->isFileHeaderRow(),
            "action"          => $feed->get('fileDataAction'),
            "attachmentId"    => $attachmentId,
            "data"            => $feed->getConfiguratorData()
        ];
    }

    public function run(array $data = []): bool
    {
        $importJob = $this->getEntityManager()->getEntity('ImportJob', $data['data']['importJobId']);
        if (empty($importJob)) {
            throw new BadRequest('No such ImportJob.');
        }

        $scope = $data['data']['entity'];

        $ids = [];

        $updatedIds = [];

        // prepare file row
        $fileRow = (int)$data['offset'];

        // create imported file
        if (empty($data['attachmentId'])) {
            $importedFileName = str_replace(' ', '_', strtolower($data['name'])) . '_' . time() . '.csv';
            $importedFilePath = $this->getContainer()->get('filePathBuilder')->createPath(FilePathBuilder::UPLOAD);
            $importedFileFullPath = $this->getConfig()->get('filesPath', 'upload/files/') . $importedFilePath;
            Util::createDir($importedFileFullPath);
            $importedFile = fopen($importedFileFullPath . '/' . $importedFileName, 'w');
        }

        while (!empty($inputData = $this->getInputData($data))) {
            foreach ($inputData as $row) {
                // push to imported file
                if (empty($data['attachmentId'])) {
                    if (empty($firstRow)) {
                        $firstRow = true;
                        fputcsv($importedFile, array_keys($row), ';');
                    }
                    fputcsv($importedFile, array_values($row), ';');
                }

                // increment file row number
                $fileRow++;

                try {
                    $entity = $this->findExistEntity($this->getService($scope)->getEntityType(), $data['data'], $row);
                    $id = null;

                    if (!empty($entity)) {
                        $id = $entity->get('id');

                        if (self::isDeleteAction($data['action'])) {
                            $ids[] = $id;
                        }

                        if (in_array($id, $updatedIds)) {
                            throw new BadRequest($this->translate('alreadyProceeded', 'exceptions', 'ImportFeed'));
                        }
                    }
                } catch (\Throwable $e) {
                    $this->log($scope, $importJob->get('id'), 'error', (string)$fileRow, $e->getMessage());
                }

                if (in_array($data['action'], ['create', 'create_delete']) && !empty($entity)) {
                    continue 1;
                }

                if (in_array($data['action'], ['update', 'update_delete']) && empty($entity)) {
                    continue 1;
                }

                if ($data['action'] == 'delete') {
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

                    if (empty($id)) {
                        $updatedEntity = $this->getService($scope)->createEntity($input);

                        if (self::isDeleteAction($data['action'])) {
                            $ids[] = $updatedEntity->get('id');
                        }

                        $this->importAttributes($attributes, $updatedEntity);
                        $this->saveRestoreRow('created', $scope, $updatedEntity->get('id'));
                    } else {
                        $notModified = true;
                        try {
                            $updatedEntity = $this->getService($scope)->updateEntity($id, $input);
                            $updatedIds[] = $id;
                            $this->saveRestoreRow('updated', $scope, [$id => $restore]);
                            $notModified = false;
                        } catch (NotModified $e) {
                        }

                        if ($this->importAttributes($attributes, $entity)) {
                            $notModified = false;
                            $updatedEntity = $entity;
                        }

                        if ($notModified) {
                            throw new NotModified();
                        }
                    }

                    if ($this->getEntityManager()->getPDO()->inTransaction()) {
                        $this->getEntityManager()->getPDO()->commit();
                    }
                } catch (\Throwable $e) {
                    if ($this->getEntityManager()->getPDO()->inTransaction()) {
                        $this->getEntityManager()->getPDO()->rollBack();
                    }

                    $message = empty($e->getMessage()) ? $this->getCodeMessage($e->getCode()) : $e->getMessage();

                    if (!$e instanceof NotModified) {
                        $this->log($scope, $importJob->get('id'), 'error', (string)$fileRow, $message);
                    }

                    continue 1;
                }

                $action = empty($id) ? 'create' : 'update';
                $this->log($scope, $importJob->get('id'), $action, (string)$fileRow, $updatedEntity->get('id'));
            }
        }

        if (self::isDeleteAction($data['action'])) {
            $toDeleteRecords = $this
                ->getEntityManager()
                ->getRepository($scope)
                ->select(['id'])
                ->where(['id!=' => $ids])
                ->find();

            if (!empty($toDeleteRecords) && count($toDeleteRecords) > 0) {
                foreach ($toDeleteRecords as $record) {
                    try {
                        if ($this->getService($scope)->deleteEntity($record->get('id'))) {
                            $this->log($scope, $importJob->get('id'), 'delete', null, $record->get('id'));
                        }
                    } catch (\Throwable $e) {
                        // ignore all
                    }
                }
            }
        }

        // save imported file
        if (empty($data['attachmentId'])) {
            fclose($importedFile);
            $attachmentRepository = $this->getEntityManager()->getRepository('Attachment');
            $attachment = $attachmentRepository->get();
            $attachment->set('name', $importedFileName);
            $attachment->set('role', 'Import');
            $attachment->set('relatedType', 'ImportJob');
            $attachment->set('relatedId', $importJob->get('id'));
            $attachment->set('storage', 'UploadDir');
            $attachment->set('storageFilePath', $importedFilePath);
            $attachment->set('type', 'text/csv');
            $attachment->set('size', \filesize($attachmentRepository->getFilePath($attachment)));
            $this->getEntityManager()->saveEntity($attachment);

            $importJob->set('attachmentId', $attachment->get('id'));
            $this->getEntityManager()->saveEntity($importJob);
        }

        return true;
    }

    public function log(string $entityName, string $importJobId, string $type, ?string $row, string $data): Entity
    {
        $log = $this->getEntityManager()->getEntity('ImportJobLog');
        $log->set('name', $row);
        $log->set('entityName', $entityName);
        $log->set('importJobId', $importJobId);
        $log->set('type', $type);

        switch ($type) {
            case 'create':
            case 'update':
                $log->set('rowNumber', (int)$row);
                $log->set('entityId', $data);
                $log->set('restoreData', $this->restore);
                break;
            case 'delete':
                $log->set('entityId', $data);
                break;
            case 'error':
                $log->set('rowNumber', (int)$row);
                $log->set('message', $data);
                break;
        }

        try {
            $this->getEntityManager()->saveEntity($log);
        } catch (\Throwable $e) {
            // ignore
        }

        $this->restore = [];

        return $log;
    }

    protected static function isDeleteAction(string $action): bool
    {
        return in_array($action, ['delete', 'create_delete', 'update_delete', 'create_update_delete']);
    }

    protected function getInputData(array $data): array
    {
        if ($this->iterations > 0) {
            return [];
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment', $data['attachmentId']);
        if (empty($attachment)) {
            throw new BadRequest('No such Attachment.');
        }

        /** @var \Import\Services\CsvFileParser $csvParser */
        $csvParser = $this->getService('CsvFileParser');

        $fileData = $csvParser->getFileData($attachment, $data['delimiter'], $data['enclosure'], $data['offset'], $data['limit']);
        if (empty($fileData)) {
            throw new BadRequest('File is empty.');
        }

        $allColumns = $csvParser->getFileColumns($attachment, $data['delimiter'], $data['enclosure'], $data['isFileHeaderRow']);

        $result = [];
        foreach ($fileData as $line => $fileLine) {
            foreach ($fileLine as $k => $v) {
                $result[$line][$allColumns[$k]] = $v;
            }
        }

        $this->iterations++;

        return $result;
    }

    protected function findExistEntity(string $entityType, array $configuration, array $row): ?Entity
    {
        $where = [];
        foreach ($configuration['configuration'] as $item) {
            if (in_array($item['name'], $configuration['idField'])) {
                $fields[] = $this->translate($item['name'], 'fields', $entityType);
                $this
                    ->getService('ImportConfiguratorItem')
                    ->getFieldConverter($this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item['name'], 'type'], 'varchar'))
                    ->prepareFindExistEntityWhere($where, $item, $row);
            }
        }

        if (empty($where)) {
            return null;
        }

        if ($this->getEntityManager()->getRepository($entityType)->where($where)->count() > 1) {
            throw new BadRequest(sprintf($this->translate('moreThanOneFound', 'exceptions', 'ImportFeed'), implode(', ', $fields)));
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

    protected function importAttributes(array $attributes, Entity $product): bool
    {
        $result = false;
        foreach ($attributes as $attribute) {
            if ($this->importAttribute($product, $attribute)) {
                $result = true;
            }
        }

        return $result;
    }

    protected function importAttribute(Entity $product, array $data): bool
    {
        $entityType = 'ProductAttributeValue';

        /** @var \Pim\Services\ProductAttributeValue $service */
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

        $pavWhere = [
            'productId'   => $product->get('id'),
            'attributeId' => $conf['attributeId'],
            'scope'       => $conf['scope'],
            'language'    => $conf['locale'],
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

        try {
            $converter->convert($inputRow, $conf, $row);
        } catch (IgnoreAttribute $e) {
            if (in_array(implode('_', $pavWhere), $this->updatedPav)) {
                throw new BadRequest($this->translate('unlinkAndLinkInOneRow', 'exceptions', 'ImportFeed'));
            }

            $this->deletedPav[] = implode('_', $pavWhere);

            if (property_exists($inputRow, 'id')) {
                $this->saveRestoreRow('deleted', $entityType, $pav->toArray());
                $service->deleteEntity($inputRow->id);
                return true;
            } else {
                return false;
            }
        }

        if (in_array(implode('_', $pavWhere), $this->deletedPav)) {
            throw new BadRequest($this->translate('unlinkAndLinkInOneRow', 'exceptions', 'ImportFeed'));
        }

        $this->updatedPav[] = implode('_', $pavWhere);

        if (!property_exists($inputRow, 'id')) {
            foreach ($pavWhere as $name => $value) {
                $inputRow->$name = $value;
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
                return false;
            }
        }

        return true;
    }

    protected function isFileValid(Entity $feed, Attachment $file): bool
    {
        // prepare settings
        $delimiter = $feed->getDelimiter();
        $enclosure = $feed->getEnclosure();
        $isFileHeaderRow = $feed->isFileHeaderRow();

        $templateColumns = $this->getService('CsvFileParser')->getFileColumns($feed->get('file'), $delimiter, $enclosure, $isFileHeaderRow);
        $fileColumns = $this->getService('CsvFileParser')->getFileColumns($file, $delimiter, $enclosure, $isFileHeaderRow);

        return $templateColumns == $fileColumns;
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
