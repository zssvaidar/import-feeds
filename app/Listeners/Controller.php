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
 * Class Controller
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
