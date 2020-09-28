<?php

declare(strict_types=1);

namespace Import\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;
use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;
use Cron\CronExpression;

/**
 * Class ImportCronJobEntity
 *
 * @author r.zablodskiy@treolabs.com
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
