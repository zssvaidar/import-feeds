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
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;
use Import\Entities\ImportFeed as ImportFeedEntity;
use Import\Entities\ImportJob;
use Espo\Entities\Attachment;

/**
 * Class ImportFeed
 */
class ImportFeed extends Base
{
    /**
     * @var null|CsvFileParser
     */
    private $csvFileParser = null;

    /**
     * @var array
     */
    protected $validFileTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain'];

    /**
     * @var array
     */
    protected $mandatorySelectAttributeList = ['allColumns'];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        foreach ($entity->getFeedFields() as $name => $value) {
            $entity->set($name, $value);
        }
    }

    /**
     * @param string $attachmentId
     * @param object $request
     *
     * @return array
     * @throws BadRequest
     */
    public function getFileColumns(string $attachmentId, $request): array
    {
        // get attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment', $attachmentId);

        if (!($attachment instanceof Attachment)) {
            throw new BadRequest($this->exception("noSuchFile"));
        }
        if (!in_array($attachment->get('type'), $this->validFileTypes)) {
            throw new BadRequest($this->exception("onlyCsvIsAllowed"));
        }

        // prepare settings
        $delimiter = (!empty($request->get('delimiter'))) ? $request->get('delimiter') : ';';
        $enclosure = ($request->get('enclosure') == 'singleQuote') ? "'" : '"';
        $isFileHeaderRow = (is_null($request->get('isHeaderRow'))) ? true : !empty($request->get('isHeaderRow'));

        return $this->getCsvFileParser()->getFileColumns($attachment, $delimiter, $enclosure, $isFileHeaderRow);
    }

    public function runImport(string $importFeedId, string $attachmentId): bool
    {
        $feed = $this->getImportFeed($importFeedId);

        // firstly, validate feed
        $this->getRepository()->validateFeed($feed);

        $serviceName = $this->getImportTypeService($feed);

        $data = $this->getServiceFactory()->create($serviceName)->prepareJobData($feed, $attachmentId);
        $data['data']['importJobId'] = $this->createImportJob($feed, $feed->getFeedField('entity'), $attachmentId)->get('id');

        $this->push($this->getName($feed), $serviceName, $data);

        return true;
    }

    public function findLinkedEntities($id, $link, $params)
    {
        if ($link === 'configuratorItems') {
            if (!empty($feed = $this->getRepository()->get($id))) {
                $allColumns = empty($feed->getFeedField('allColumns')) ? [] : $feed->getFeedField('allColumns');
                $this->removeItemsByAllColumns($feed, $allColumns);
            }
        }

        return parent::findLinkedEntities($id, $link, $params);
    }

    public function removeItemsByAllColumns(Entity $importFeed, array $allColumns): void
    {
        $items = $importFeed->get('configuratorItems');
        if (!empty($items) && count($items) > 0) {
            foreach ($items as $item) {
                if (!empty($columns = $item->get('column'))) {
                    foreach ($columns as $column) {
                        if (!in_array($column, $allColumns)) {
                            $this->getEntityManager()->removeEntity($item);
                            continue 2;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function exception(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'exceptions', 'ImportFeed');
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
        $this->addDependency('queueManager');
    }

    protected function duplicateConfiguratorItems(Entity $entity, Entity $duplicatingEntity): void
    {
        if (empty($items = $duplicatingEntity->get('configuratorItems')) || count($items) === 0) {
            return;
        }

        foreach ($items as $item) {
            $data = $item->toArray();
            unset($data['id']);
            $data['importFeedId'] = $entity->get('id');

            $newItem = $this->getEntityManager()->getEntity('ImportConfiguratorItem');
            $newItem->set($data);
            $this->getEntityManager()->saveEntity($newItem);
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function translate(string $key): string
    {
        return $this->getInjection('language')->translate($key, 'labels', 'ImportFeed');
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     */
    protected function push(string $name, string $serviceName, array $data = []): bool
    {
        return $this->getInjection('queueManager')->push($name, $serviceName, $data);
    }

    /**
     * @param Attachment $attachment
     * @param string     $delimiter
     * @param string     $enclosure
     *
     * @return int
     * @throws BadRequest
     */
    protected function getCountRows(Attachment $attachment, string $delimiter = ";", string $enclosure = '"'): int
    {
        $count = $this->getCsvFileParser()->getCountRows($attachment, $delimiter, $enclosure);

        if ($count < 1) {
            throw new BadRequest($this->exception("countOfFileRowsIsLessThanOne"));
        }

        return $count;
    }

    /**
     * @param string $importFeedId
     *
     * @return ImportFeedEntity
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    protected function getImportFeed(string $importFeedId): ImportFeedEntity
    {
        $feed = $this->getEntityManager()->getEntity('ImportFeed', $importFeedId);
        if (empty($feed)) {
            throw new NotFound($this->exception("No such ImportFeed"));
        }

        // checking rules
        if (!$this->getAcl()->check($feed, 'read')) {
            throw new Forbidden();
        }

        // is feed active ?
        if (!$feed->get('isActive')) {
            throw new BadRequest($this->exception("importFeedIsInactive"));
        }

        return $feed;
    }

    /**
     * @return CsvFileParser
     */
    protected function getCsvFileParser(): CsvFileParser
    {
        if (is_null($this->csvFileParser)) {
            $this->csvFileParser = $this->getInjection('serviceFactory')->create('CsvFileParser');
        }

        return $this->csvFileParser;
    }

    /**
     * @param ImportFeedEntity $feed
     *
     * @return string
     */
    protected function getName(ImportFeedEntity $feed): string
    {
        return $this->translate("Import") . ": <strong>{$feed->get("name")}</strong>";
    }

    /**
     * @param ImportFeedEntity $feed
     *
     * @return string
     */
    protected function getImportTypeService(ImportFeedEntity $feed): string
    {
        return "ImportType" . ucfirst($feed->get('type'));
    }

    /**
     * @param ImportFeedEntity $feed
     * @param string           $entityType
     * @param string           $attachmentId
     *
     * @return ImportJob
     */
    protected function createImportJob(ImportFeedEntity $feed, string $entityType, string $attachmentId): ImportJob
    {
        $entity = $this->getEntityManager()->getEntity('ImportJob');
        $entity->set('name', date('Y-m-d H:i:s'));
        $entity->set('importFeedId', $feed->get('id'));
        $entity->set('entityName', $entityType);
        if (!empty($attachmentId)) {
            $entity->set('attachmentId', $attachmentId);
        }

        $this->getEntityManager()->saveEntity($entity);

        return $entity;
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        parent::beforeUpdateEntity($entity, $data);

        foreach ($entity->getFeedFields() as $name => $value) {
            if (!$entity->has($name)) {
                $entity->set($name, $value);
            }
        }
    }

    protected function getFieldsThatConflict(Entity $entity, \stdClass $data): array
    {
        return [];
    }

    protected function isEntityUpdated(Entity $entity, \stdClass $data): bool
    {
        return true;
    }
}
