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

namespace Import\FieldConverters;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Services\Attachment as AttachmentService;
use Treo\Core\FilePathBuilder;

/**
 * Class Asset
 */
class Asset extends Varchar
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
        // prepare default entity id
        $value = $config['default'];

        // prepare default entity name
        $name = isset($config['defaultName']) ? $config['defaultName'] : null;

        if (!empty($row[$config['column'][0]])) {
            // get entity name
            $entityName = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);

            $values = explode('|', $row[$config['column'][0]]);
            $where = [];
            foreach ($config['field'] as $k => $field) {
                if ($field != 'url') {
                    $where[$field] = $values[$k];
                } else {
                    $url = $values[$k];
                }
            }

            if (!empty($where)) {
                $entity = $this->getEntityManager()
                    ->getRepository($entityName)
                    ->select(['id', 'name'])
                    ->where($where)
                    ->findOne();
            }

            if (!empty($entity)) {
                $value = $entity->get('id');
                $name = $entity->get('name');
            } else {
                if (empty($config['createIfNotExist'])) {
                    throw new BadRequest("No related entity found.");
                }

                if (!empty($url)) {
                    $attachment = $this->createAttachment((string)$url, $entityName, (string)$config['name']);
                    $value = $attachment->get('id');
                    $name = $attachment->get('name');
                }
            }
        }

        $inputRow->{$config['name'] . 'Id'} = $value;
        $inputRow->{$config['name'] . 'Name'} = $name;
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

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
        if ($entity->has('defaultId')) {
            $entity->set('default', empty($entity->get('defaultId')) ? null : $entity->get('defaultId'));
        }
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
    {
        $entity->set('defaultId', null);
        $entity->set('defaultName', null);
        $entity->set('defaultPathsData', null);
        if (!empty($entity->get('default'))) {
            $entity->set('defaultId', $entity->get('default'));
            $relEntity = $this->getEntityManager()->getEntity('Attachment', $entity->get('defaultId'));
            $entity->set('defaultName', empty($relEntity) ? $entity->get('defaultId') : $relEntity->get('name'));
            $entity->set('defaultPathsData', $this->getEntityManager()->getRepository('Attachment')->getAttachmentPathsData($relEntity));
        }
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
