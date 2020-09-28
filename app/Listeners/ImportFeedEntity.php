<?php

declare(strict_types=1);

namespace Import\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;

/**
 * Class ImportFeedEntity
 *
 * @author r.zablodskiy@treolabs.com
 */
class ImportFeedEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        $entity = $event->getArgument('entity');

        if (!$this->isFileValid($entity)) {
            throw new Error($this->exception('File invalid. Only CSV is allowed.'));
        }

        if (!$this->isConfiguratorValid($entity)) {
            throw new Error($this->exception('Configurator settings incorrect'));
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isFileValid(Entity $entity): bool
    {
        // get file
        $file = $entity->get('file');

        // for simple type
        if ($entity->get('type') == 'simple') {
            return ((!empty($file) && in_array($file->get('type'), ['text/csv', 'application/vnd.ms-excel', 'text/plain'])) || empty($file));
        }

        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isConfiguratorValid(Entity $entity): bool
    {
        $configurator = Json::decode(Json::encode($entity->get('data')->configuration), true);

        foreach ($configurator as $key => $item) {
            // if don't set file column and default values
            if ($item['column'] == '' && $item['default'] == '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getContainer()->get('language')->translate($key, 'exceptions', 'ImportFeed');
    }
}
