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

namespace Import\Types\Simple\FieldConverters;

use Espo\Core\Exceptions\Error;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Services\Attachment as AttachmentService;
use Treo\Core\FilePathBuilder;

/**
 * Class Asset
 */
class Asset extends AbstractConverter
{
    /**
     * @var null|AttachmentService
     */
    private $attachmentService = null;

    /**
     * @inheritDoc
     *
     * @throws Error
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
        if (!empty($row[$config['column'][0]])) {
            $attachment = $this->createAttachment((string)$row[$config['column'][0]], $entityType, (string)$config['name']);
            $inputRow->{$config['name'] . 'Id'} = $attachment->get('id');
            $inputRow->{$config['name'] . 'Name'} = $attachment->get('name');
        } elseif (!empty($config['default'])) {
            $inputRow->{$config['name'] . 'Id'} = $config['default'];
            $inputRow->{$config['name'] . 'Name'} = $config['defaultName'];
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        $value = null;

        if (!empty($foreign = $entity->get($item['name']))) {
            $value = $foreign->get('id');
        }

        $restore->{$item['name'] . 'Id'} = $value;
    }

    protected function createAttachment(string $url, string $relatedType, string $field): Attachment
    {
        $attachment = new \stdClass();
        $attachment->name = basename($url);
        $attachment->relatedType = $relatedType;
        $attachment->field = $field;
        $attachment->storageFilePath = $this->getAttachmentRepository()->getDestPath(FilePathBuilder::UPLOAD);
        $attachment->storageThumbPath = $this->getAttachmentRepository()->getDestPath(FilePathBuilder::UPLOAD);

        $fullPath = $this->getConfig()->get('filesPath', 'upload/files/') . $attachment->storageFilePath;
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        $attachment->fileName = $fullPath . '/' . $attachment->name;

        $file = fopen($url, 'r');
        if ($file) {
            file_put_contents($attachment->fileName, $file);
        }

        if (!file_exists($attachment->fileName)) {
            throw new Error("File '$url' download failed.");
        }

        return $this->getAttachmentService()->createEntity($attachment);
    }

    protected function getAttachmentRepository(): AttachmentRepository
    {
        return $this->container->get('entityManager')->getRepository('Attachment');
    }

    protected function getAttachmentService(): AttachmentService
    {
        if (is_null($this->attachmentService)) {
            $this->attachmentService = $this->container->get('serviceFactory')->create('Attachment');
        }

        return $this->attachmentService;
    }
}
