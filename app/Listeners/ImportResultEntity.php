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

namespace Import\Listeners;

use Import\Entities\ImportResult;
use Treo\Entities\Attachment;
use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class ImportResultEntity
 */
class ImportResultEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // prepare entity
        $entity = $event->getArgument('entity');

        if ($entity->isAttributeChanged('state') && $entity->get('state') == 'Done') {
            $this->generateErrorsAttachment($entity);
        }
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // prepare entity
        $entity = $event->getArgument('entity');

        // prepare id
        $id = $entity->get('id');

        $this->deleteLog($id);
        $this->deleteQueueItem($id);
        $this->deleteAttachments($entity);
    }

    /**
     * @param string $id
     */
    protected function deleteLog(string $id): void
    {
        $this
            ->getEntityManager()
            ->getPDO()
            ->exec("UPDATE import_result_log SET deleted=1 WHERE import_result_id='$id'");
    }

    /**
     * @param string $id
     */
    protected function deleteQueueItem(string $id): void
    {
        $this
            ->getEntityManager()
            ->getPDO()
            ->exec("UPDATE queue_item SET deleted=1 WHERE data LIKE '%\"importResultId\":\"$id\"%'");
    }

    /**
     * @param ImportResult $entity
     */
    protected function deleteAttachments(ImportResult $entity): void
    {
        if (!empty($attachment = $entity->get('attachment'))) {
            $this->getEntityManager()->removeEntity($attachment);
        }
        if (!empty($attachment = $entity->get('errorsAttachment'))) {
            $this->getEntityManager()->removeEntity($attachment);
        }
    }

    /**
     * @param ImportResult $importResult
     *
     * @return bool
     */
    protected function generateErrorsAttachment(ImportResult $importResult): bool
    {
        // get errors rows numbers
        if (empty($errorsRowsNumbers = $this->getErrorsRows((string)$importResult->get('id')))) {
            return false;
        }

        // get importFeed
        $feed = $importResult->get('importFeed');

        // add header row if it needs
        if (!empty($feed->get('isFileHeaderRow'))) {
            $errorsRowsNumbers[] = 1;
        }

        // get file data
        $data = $this->getFileData($importResult->get('attachment'), $feed->getDelimiter(), $feed->getEnclosure());

        // collect errors rows
        $errorsRows = [];
        foreach ($data as $k => $row) {
            if (in_array($k + 1, $errorsRowsNumbers)) {
                $errorsRows[] = $row;
            }
        }

        // generate contents
        $contents = $this->generateCsvContents($errorsRows, $feed->getDelimiter(), $feed->getEnclosure());

        // create attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', 'errors-' . $importResult->get('attachment')->get('name'));
        $attachment->set('field', 'errorsAttachment');
        $attachment->set('role', 'Attachment');
        $attachment->set('type', 'text/csv');

        // store file
        $this
            ->getContainer()
            ->get('fileStorageManager')
            ->putContents($attachment, $contents);

        // save new attachment
        $this->getEntityManager()->saveEntity($attachment);

        // update importResult
        $importResult->set('errorsAttachmentId', $attachment->get('id'));
        $this->getEntityManager()->saveEntity($importResult, ['skipAll' => true]);

        return true;
    }


    /**
     * @param $data
     * @param $delimiter
     * @param $enclosure
     *
     * @return string
     */
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

    /**
     * @param string $importResultId
     *
     * @return array
     */
    protected function getErrorsRows(string $importResultId): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('ImportResultLog')
            ->select(['rowNumber'])
            ->where(
                [
                    'importResultId' => $importResultId,
                    'type'           => 'error'
                ]
            )
            ->find()
            ->toArray();

        return array_column($data, 'rowNumber');
    }

    /**
     * @param Attachment $attachment
     * @param string     $delimiter
     * @param string     $enclosure
     *
     * @return array
     */
    protected function getFileData(Attachment $attachment, string $delimiter, string $enclosure): array
    {
        return $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('CsvFileParser')
            ->getFileData($attachment, $delimiter, $enclosure);
    }
}
