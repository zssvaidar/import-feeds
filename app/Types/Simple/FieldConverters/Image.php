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
use Espo\ORM\Entity;

/**
 * Class Image
 */
class Image extends AbstractConverter
{
    /**
     * @inheritDoc
     *
     * @throws Error
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        if (!empty($row[$config['column']])) {
            // get contents
            $contents = @file_get_contents($row[$config['column']]);

            if (empty($contents)) {
                throw new Error('Wrong image link. Link: ' . $row[$config['column']]);
            }

            // get entity manager
            $em = $this->container->get('entityManager');

            // create attachment
            $attachment = $em->getEntity('Attachment');
            $attachment->set('name', array_pop(explode("/", $row[$config['column']])));
            $attachment->set('field', $config['name']);
            $attachment->set('role', 'Attachment');

            // get file storage manager
            $sm = $this->container->get('fileStorageManager');

            // store file
            $sm->putContents($attachment, $contents);

            // get mime type
            $type = mime_content_type($sm->getLocalFilePath($attachment));

            if (!in_array($type, ['image/jpeg', 'image/png', 'image/gif'])) {
                $sm->unlink($attachment);
                throw new Error('Wrong file mime type. Only image allowed. Link:' . $row[$config['column']]);
            } else {
                // set mime type
                $attachment->set('type', $type);

                // save attachment
                $em->saveEntity($attachment);

                $inputRow->{$config['name'] . 'Id'} = $attachment->get('id');
                $inputRow->{$config['name'] . 'Name'} = $attachment->get('name');
            }
        } elseif (!empty($config['default'])) {
            $inputRow->{$config['name'] . 'Id'} = $config['default'];
            $inputRow->{$config['name'] . 'Name'} = $config['defaultName'];
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        $value = null;

        if (!empty($foreign = $entity->get($item['name']))) {
            $value = $foreign->get('id');
        }

        $restore->{$item['name'] . 'Id'} = $value;
    }
}
