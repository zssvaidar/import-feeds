<?php

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;

/**
 * Class Image
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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
