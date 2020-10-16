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
