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
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;
use Cron\CronExpression;

/**
 * Class ImportCronJobEntity
 */
class ImportCronJobEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws Error
     */
    public function beforeSave(Event $event)
    {
        $entity = $event->getArgument('entity');

        if (!$this->isValid($entity)) {
            throw new Error('Link or Scheduling are not correct');
        }

        // create or update scheduled job
        $this->updateScheduledJob($entity);
    }

    /**
     * @param Event $event
     */
    public function beforeRemove(Event $event)
    {
        $entity = $event->getArgument('entity');

        // delete scheduled job
        $this->deleteScheduledJob($entity);
    }

    /**
     * Is form valid
     *
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isValid(Entity $entity): bool
    {
        // prepare result
        $result = false;

        if (filter_var($entity->get('link'), FILTER_VALIDATE_URL) && !empty($entity->get('scheduling'))) {
            $result = true;
            try {
                $cronExpression = CronExpression::factory($entity->get('scheduling'));
            } catch (\Exception $e) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Create or update scheduled job
     *
     * @param Entity $entity
     *
     * @throws Error
     */
    protected function updateScheduledJob(Entity $entity): void
    {
        // get $scheduledJob
        $scheduledJob = $this
            ->getEntityManager()
            ->getRepository('ScheduledJob')
            ->where(['importCronJobId' => $entity->get('id')])
            ->findOne();
        if (empty($scheduledJob)) {
            $scheduledJob = $this->getEntityManager()->getEntity('ScheduledJob');
        }

        // prepare scheduledJob
        $scheduledJob->set(
            [
                'name'            => $entity->get('name'),
                'scheduling'      => $entity->get('scheduling'),
                'job'             => 'ImportScheduledJob',
                'importCronJobId' => $entity->get('id'),
                'status'          => ($entity->get('isActive')) ? 'Active' : 'Inactive',
                'isInternal'      => true,
            ]
        );

        // save
        $this->getEntityManager()->saveEntity($scheduledJob);
    }

    /**
     * Delete scheduled job
     *
     * @param Entity $entity
     */
    protected function deleteScheduledJob(Entity $entity): void
    {
        $scheduledJob = $this
            ->getEntityManager()
            ->getRepository('ScheduledJob')
            ->where(['importCronJobId' => $entity->get('id')])
            ->findOne();

        if (!empty($scheduledJob)) {
            $this->getEntityManager()->removeEntity($scheduledJob);
        }
    }
}
