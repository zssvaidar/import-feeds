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

namespace Import\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Services\Attachment;
use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheet;

class ImportJob extends Base
{
    public const IMPORT_ERRORS_COLUMN = 'Import Errors';

    protected function beforeSave(Entity $entity, array $options = [])
    {
        $importFeed = $entity->get('importFeed');
        if (empty($importFeed)) {
            throw new BadRequest('Import Feed is required.');
        }

        if ($entity->isAttributeChanged('state')) {
            if ($entity->get('state') == 'Running') {
                $entity->set('start', date('Y-m-d H:i:s'));
            } elseif ($entity->get('state') == 'Success') {
                $entity->set('end', date('Y-m-d H:i:s'));
            }
        }

        parent::beforeSave($entity, $options);
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        if ($entity->isAttributeChanged('state') && $entity->get('state') == 'Success') {
            $this->generateErrorsAttachment($entity);
        }

        parent::afterSave($entity, $options);

        if (!empty($importFeed = $entity->get('importFeed'))) {
            $jobs = $this->where(['importFeedId' => $importFeed->get('id'), 'state' => 'Success'])->order('createdAt')->find();
            $jobsCount = count($jobs);
            foreach ($jobs as $job) {
                if ($jobsCount > $importFeed->get('jobsMax')) {
                    $this->getEntityManager()->removeEntity($job);
                    $jobsCount--;
                }
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        $id = (string)$entity->get('id');

        $this->exec("DELETE FROM `import_job_log` WHERE import_job_id='$id'");
        $this->exec("DELETE FROM `queue_item` WHERE data LIKE '%\"importJobId\":\"$id\"%'");

        if (
            !empty($attachment = $entity->get('attachment'))
            && !empty($importFeed = $entity->get('importFeed'))
            && $importFeed->get('fileId') !== $attachment->get('id')
        ) {
            $this->getEntityManager()->removeEntity($attachment);
        }

        if (!empty($errorsAttachment = $entity->get('errorsAttachment'))) {
            $this->getEntityManager()->removeEntity($errorsAttachment);
        }

        parent::afterRemove($entity, $options);
    }

    public function generateErrorsAttachment(Entity $importJob): bool
    {
        $errorLogs = $this
            ->getEntityManager()
            ->getRepository('ImportJobLog')
            ->select(['rowNumber', 'message'])
            ->where([
                'importJobId' => $importJob->get('id'),
                'type'        => 'error'
            ])
            ->find()
            ->toArray();

        if (empty($errorLogs)) {
            return false;
        }

        // get importFeed
        $feed = $importJob->get('importFeed');

        $errorsRowsNumbers = [];

        // add header row if it needs
        if (!empty($feed->getFeedField('isFileHeaderRow')) || $feed->get('type') !== 'simple') {
            $errorsRowsNumbers[1] = self::IMPORT_ERRORS_COLUMN;
        }

        foreach ($errorLogs as $log) {
            $rowNumber = (int)$log['rowNumber'];
            $errorsRowsNumbers[$rowNumber] = $log['message'];
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment', $importJob->get('attachmentId'));

        $fileParser = $this->getInjection('serviceFactory')->create('ImportFeed')->getFileParser($feed->getFeedField('format'));

        // get file data
        $data = $fileParser->getFileData($attachment, $feed->getDelimiter(), $feed->getEnclosure());

        // collect errors rows
        $errorsRows = [];
        foreach ($data as $k => $row) {
            $key = $k + 1;
            if (isset($errorsRowsNumbers[$key])) {
                $row[] = $errorsRowsNumbers[$key];
                $errorsRows[] = $row;
            }
        }

        /** @var Attachment $attachmentService */
        $attachmentService = $this->getInjection('serviceFactory')->create('Attachment');

        // prepare attachment name
        $nameParts = explode('.', $importJob->get('attachment')->get('name'));
        array_pop($nameParts);
        $name = 'errors-' . implode('.', $nameParts);

        $inputData = new \stdClass();
        $inputData->name = "{$name}.csv";
        $inputData->contents = $this->generateCsvContents($errorsRows, $feed->getDelimiter(), $feed->getEnclosure());
        $inputData->type = 'text/csv';
        $inputData->relatedType = 'ImportJob';
        $inputData->field = 'errorsAttachment';
        $inputData->role = 'Attachment';

        $attachment = $attachmentService->createEntity($inputData);

        // create xlsx
        if ($feed->getFeedField('format') === 'Excel') {
            $filePath = $this->getEntityManager()->getRepository('Attachment')->getFilePath($attachment);
            $cacheDir = 'data/cache';

            Util::createDir($cacheDir);
            $cacheFile = "{$cacheDir}/{$name}.xlsx";

            $reader = PhpSpreadsheet::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $reader->setDelimiter($feed->getDelimiter());
            $reader->setEnclosure($feed->getEnclosure());
            $writer = PhpSpreadsheet::createWriter($reader->load($filePath), "Xlsx");
            $writer->save($cacheFile);

            $inputData->name = "{$name}.xlsx";
            $inputData->type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $inputData->contents = file_get_contents($cacheFile);

            // remove csv
            $this->getEntityManager()->removeEntity($attachment);

            // remove cache file
            unlink($cacheFile);

            // create xlsx
            $attachment = $attachmentService->createEntity($inputData);
        }

        $importJob->set('errorsAttachmentId', $attachment->get('id'));
        $this->getEntityManager()->saveEntity($importJob, ['skipAll' => true]);

        return true;
    }

    protected function generateCsvContents($data, $delimiter, $enclosure): string
    {
        // prepare file name
        $fileName = 'data/tmp_import_file.csv';

        // create file
        $fp = fopen($fileName, 'w');
        foreach ($data as $fields) {
            fputcsv($fp, $fields, $delimiter, $enclosure);
        }
        fclose($fp);

        // get contents
        $contents = file_get_contents($fileName);

        // delete file
        unlink($fileName);

        return $contents;
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('serviceFactory');
        $this->addDependency('fileStorageManager');
    }

    private function exec(string $sql): void
    {
        $this->getEntityManager()->nativeQuery($sql);
    }
}
