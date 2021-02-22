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

use Espo\Core\Exceptions\Error;
use Import\Entities\ImportFeed;
use Import\Types\Simple\Handlers\DefaultHandler;
use Espo\Entities\Attachment;
use Treo\Services\QueueManagerBase;

/**
 * Class ImportTypeSimple
 */
class ImportTypeSimple extends QueueManagerBase
{
    /**
     * @param ImportFeed $feed
     *
     * @return string
     */
    public function getEntityType(ImportFeed $feed): string
    {
        return $feed->get('data')->entity;
    }

    /**
     * @inheritdoc
     *
     * @throws Error
     */
    public function run(array $data = []): bool
    {
        // validation
        if (empty($data['attachmentId'])
            || empty($data['action'])
            || !in_array($data['action'], ['create', 'update', 'create_update'])
            || empty($attachment = $this->getAttachment($data['attachmentId']))
            || empty($fileData = $this->getFileData($attachment, $data))) {
            return false;
        }

        // get class name
        $className = $this
            ->getContainer()
            ->get('metadata')
            ->get(['scopes', $data['data']['entity'], 'importTypeSimple', 'handler'], DefaultHandler::class);

        if (!class_exists($className)) {
            throw new Error('No such import handler class');
        }

        try {
            $object = new $className($this->getContainer());
        } catch (\Throwable $e) {
            throw new Error('Cann\'t create import handler object');
        }

        if (!method_exists($object, 'run')) {
            throw new Error('Run method is required');
        }

        return $object->run($fileData, $data);
    }

    /**
     * @param Attachment $attachment
     * @param array      $data
     *
     * @return array
     */
    protected function getFileData(Attachment $attachment, array $data): array
    {
        return $this
            ->getContainer()
            ->get('serviceFactory')
            ->create('CsvFileParser')
            ->getFileData($attachment, $data['delimiter'], $data['enclosure'], $data['offset'], $data['limit']);
    }

    /**
     * @param string $id
     *
     * @return Attachment
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function getAttachment(string $id): ?Attachment
    {
        return $this->getEntityManager()->getEntity('Attachment', $id);
    }
}
