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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Import\Entities\ImportFeed as ImportFeedEntity;
use Import\Entities\ImportResult;
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

        return $this
            ->getCsvFileParser()
            ->getFileColumns($attachment, $delimiter, $enclosure, $isFileHeaderRow);
    }

    /**
     * @param string $importFeedId
     * @param string $attachmentId
     *
     * @return bool
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function runImport(string $importFeedId, string $attachmentId): bool
    {
        // get importFeed
        $feed = $this->getImportFeed($importFeedId);

        // prepare data
        $data = $this->getPrepareData($feed, $attachmentId);

        // check if empty configuration data
        if (empty($data['data'])) {
            throw new Error($this->exception('configuratorSettingsIncorrect'));
        }

        // create service
        try {
            $service = $this->getServiceFactory()->create($this->getImportTypeService($feed));
        } catch (\Throwable $e) {
        }

        // get count
        $count = $this->getCountRows($this->getFile($attachmentId, $feed), $feed->getDelimiter(), $feed->getEnclosure());

        // create import result
        if (!empty($service) && method_exists($service, 'getEntityType')) {
            $data['data']->importResultId = $this
                ->createImportResult($feed, $service->getEntityType($feed), $attachmentId)
                ->get('id');
        }

        // prepare is header row true
        if ($data['isFileHeaderRow']) {
            // prepare count
            $count--;
            // prepare offset
            $data['offset'] = 1;
        }

        if ($count <= $data['limit']) {
            // push
            $this->push($this->getName($feed), $this->getImportTypeService($feed), $data);
        } else {
            $offset = $data['offset'];
            while ($offset < $count) {
                // prepare offset
                $data["offset"] = $offset;

                // prepare $to
                $to = $offset + $data['limit'];
                $to = ($to > $count) ? $count : $to;

                // push
                $this->push($this->getName($feed) . " [$offset-$to]", $this->getImportTypeService($feed), $data);

                // increase
                $offset = $offset + $data['limit'];
            }
        }

        return true;
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
     * @param string           $attachmentId
     * @param ImportFeedEntity $feed
     *
     * @return Attachment
     * @throws BadRequest
     * @throws NotFound
     */
    protected function getFile(string $attachmentId, ImportFeedEntity $feed): Attachment
    {
        $file = $this->getEntityManager()->getEntity('Attachment', $attachmentId);

        // is file exists ?
        if (empty($file)) {
            throw new NotFound($this->exception("noSuchFile"));
        }

        // is file valid ?
        if (!$this->isFileValid($feed, $file)) {
            throw new BadRequest($this->exception("theFileDoesNotMatchTheTemplate"));
        }

        return $file;
    }

    /**
     * @param ImportFeedEntity $feed
     * @param Attachment       $file
     *
     * @return bool
     */
    protected function isFileValid(ImportFeedEntity $feed, Attachment $file): bool
    {
        // prepare settings
        $delimiter = $feed->getDelimiter();
        $enclosure = $feed->getEnclosure();
        $isFileHeaderRow = $feed->isFileHeaderRow();

        // get columns
        $templateColumns = $this
            ->getCsvFileParser()
            ->getFileColumns($feed->get('file'), $delimiter, $enclosure, $isFileHeaderRow);
        $fileColumns = $this->getCsvFileParser()->getFileColumns($file, $delimiter, $enclosure, $isFileHeaderRow);

        return array_column($templateColumns, 'name') == array_column($fileColumns, 'name');
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
     * @param string           $attachmentId
     *
     * @return array
     */
    protected function getPrepareData(ImportFeedEntity $feed, string $attachmentId): array
    {
        return [
            "offset"          => 0,
            "limit"           => $feed->get('limit'),
            "delimiter"       => $feed->getDelimiter(),
            "enclosure"       => $feed->getEnclosure(),
            "isFileHeaderRow" => $feed->isFileHeaderRow(),
            "action"          => $feed->get('fileDataAction'),
            "decimalMark"     => $feed->get('decimalMark'),
            "attachmentId"    => $attachmentId,
            "data"            => $feed->get('data')
        ];
    }

    /**
     * @param ImportFeedEntity $feed
     * @param string           $entityType
     * @param string           $attachmentId
     *
     * @return ImportResult
     */
    protected function createImportResult(ImportFeedEntity $feed, string $entityType, string $attachmentId): ImportResult
    {
        $entity = $this->getEntityManager()->getEntity('ImportResult');
        $entity->set('name', date('Y-m-d H:i:s'));
        $entity->set('importFeedId', $feed->get('id'));
        $entity->set('entityName', $entityType);
        $entity->set('attachmentId', $attachmentId);

        $this->getEntityManager()->saveEntity($entity);

        return $entity;
    }

}
