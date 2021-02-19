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

/**
 * Class QueueItemEntity
 */
class QueueItemEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // prepare entity
        $entity = $event->getArgument('entity');

        if (!empty($importResultId = $entity->get('data')->data->importResultId)) {
            $this->updateImportResultState((string)$importResultId);
        }
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // prepare entity
        $entity = $event->getArgument('entity');

        if (!empty($importResultId = $entity->get('data')->data->importResultId)) {
            $this->updateImportResultState((string)$importResultId);
        }
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    private function updateImportResultState(string $id): bool
    {
        // find import result
        $importResult = $this->getEntityManager()->getEntity('ImportResult', $id);

        if (empty($importResult) || $importResult->get('state') == 'Done') {
            return false;
        }

        // get count of pending
        $pending = $this
            ->getEntityManager()
            ->getRepository('QueueItem')
            ->where(['data*' => '%"importResultId":"' . $id . '"%', 'status' => 'Pending'])
            ->count();

        if (empty($pending)) {
            $importResult->set('state', 'Done');
            $importResult->set('end', date('Y-m-d H:i:s'));
        }

        // get count of running items
        $running = $this
            ->getEntityManager()
            ->getRepository('QueueItem')
            ->where(['data*' => '%"importResultId":"' . $id . '"%', 'status' => 'Running'])
            ->count();

        if (!empty($running)) {
            $importResult->set('state', 'Running');
            if (empty($importResult->get('start'))) {
                $importResult->set('start', date('Y-m-d H:i:s'));
            }
            $importResult->set('end', null);
        }

        $this->getEntityManager()->saveEntity($importResult);

        return true;
    }
}
