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
