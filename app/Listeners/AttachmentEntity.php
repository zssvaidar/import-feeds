<?php

declare(strict_types=1);

namespace Import\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class AttachmentEntity
 *
 * @author r.zablodskiy@treolabs.com
 */
class AttachmentEntity extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        $entity = $event->getArgument('entity');

        if ($entity->get('relatedType') == 'ImportFeed' && !empty($entity->get('tmpPath'))) {
            $entity->setIsNew(false);
            $entity->set('storage', 'UploadDir');
            $this->getContainer()->get('serviceFactory')->create($entity->getEntityType())->moveFromTmp($entity);
            $this->getEntityManager()->saveEntity($entity, ['skipAll' => true]);
        }
    }
}
