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

namespace Import\Repositories;

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

/**
 * Class ImportResult
 */
class ImportResult extends Base
{
    public const IMPORT_ERRORS_COLUMN = 'Import Errors';

    protected function afterSave(Entity $entity, array $options = [])
    {
        if ($entity->isAttributeChanged('state') && $entity->get('state') == 'Done') {
            $this->generateErrorsAttachment($entity);
        }

        parent::afterSave($entity, $options);
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        $id = (string)$entity->get('id');

        $this->exec("UPDATE import_result_log SET deleted=1 WHERE import_result_id='$id'");
        $this->exec("UPDATE queue_item SET deleted=1 WHERE data LIKE '%\"importResultId\":\"$id\"%'");

        if (!empty($attachment = $entity->get('errorsAttachment'))) {
            $this->getEntityManager()->removeEntity($attachment);
        }

        parent::afterRemove($entity, $options);
    }

    public function generateErrorsAttachment(Entity $importResult): bool
    {
        $errorsRows = $this
            ->getEntityManager()
            ->getRepository('ImportResultLog')
            ->select(['rowNumber', 'message'])
            ->where([
                'importResultId' => $importResult->get('id'),
                'type'           => 'error'
            ])
            ->find()
            ->toArray();

        if (empty($errorsRows)) {
            return false;
        }

        $errorsRowsNumbers = array_column($errorsRows, 'message', 'rowNumber');

        // get importFeed
        $feed = $importResult->get('importFeed');

        // add header row if it needs
        if (!empty($feed->get('isFileHeaderRow'))) {
            $errorsRowsNumbers[1] = self::IMPORT_ERRORS_COLUMN;
        }

        // get file data
        $data = $this
            ->getInjection('serviceFactory')
            ->create('CsvFileParser')
            ->getFileData($importResult->get('attachment'), $feed->getDelimiter(), $feed->getEnclosure());

        // collect errors rows
        $errorsRows = [];
        foreach ($data as $k => $row) {
            $key = $k + 1;
            if (isset($errorsRowsNumbers[$key])) {
                $row[] = $errorsRowsNumbers[$key];
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
            ->getInjection('fileStorageManager')
            ->putContents($attachment, $contents);

        // save new attachment
        $this->getEntityManager()->saveEntity($attachment);

        // update importResult
        $importResult->set('errorsAttachmentId', $attachment->get('id'));
        $this->getEntityManager()->saveEntity($importResult, ['skipAll' => true]);

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
