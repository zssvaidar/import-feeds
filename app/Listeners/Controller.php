<?php

declare(strict_types=1);

namespace Import\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class Controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Controller extends AbstractListener
{
    /**
     * @param Event $event
     */
    public function beforeAction(Event $event)
    {
        // get controller
        $controller = $event->getArgument('controller');

        // get request
        $request = $event->getArgument('request');

        if (!empty($where = $request->get('where'))) {
            foreach ($where as $k => $item) {
                // for create type
                if (isset($item['attribute']) && $item['attribute'] == 'createdByImportId') {
                    $where[$k] = [
                        'type'      => 'in',
                        'attribute' => 'id',
                        'value'     => $this->getLogIds('create', $controller, $item['value'])
                    ];
                }

                // for update type
                if (isset($item['attribute']) && $item['attribute'] == 'updatedByImportId') {
                    $where[$k] = [
                        'type'      => 'in',
                        'attribute' => 'id',
                        'value'     => $this->getLogIds('update', $controller, $item['value'])
                    ];
                }
            }

            // set where
            $request->setQuery('where', $where);
        }
    }

    /**
     * @param string $type
     *
     * @return array
     */
    private function getLogIds(string $type, string $entityName, string $importResultId): array
    {
        $data = $this
            ->getEntityManager()
            ->getRepository('ImportResultLog')
            ->select(['entityId'])
            ->where(
                [
                    'type'           => $type,
                    'entityName'     => $entityName,
                    'importResultId' => $importResultId
                ]
            )
            ->find()->toArray();

        return array_column($data, 'entityId');
    }
}
