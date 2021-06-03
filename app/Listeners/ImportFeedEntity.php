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

namespace Import\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;

/**
 * Class ImportFeedEntity
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
            throw new Error($this->exception('onlyCsvIsAllowed'));
        }

        if (!$this->isConfiguratorValid($entity)) {
            throw new Error($this->exception('configuratorSettingsIncorrect'));
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
            if ($item['column'] == [] && $item['default'] == '') {
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
