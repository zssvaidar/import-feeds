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

namespace Import\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

/**
 * Class ImportResult
 */
class ImportResult extends Base
{
    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('language');
        $this->addDependency('queueManager');
    }

    /**
     * @inheritDoc
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        // prepare id
        $id = (string)$entity->get('id');

        $entity->set('createdCount', $this->getLogCount('create', $id));
        $entity->set('updatedCount', $this->getLogCount('update', $id));
        $entity->set('errorsCount', $this->getLogCount('error', $id));
    }

    /**
     * @param string $importResultId
     *
     * @return bool
     *
     * @throws BadRequest
     * @throws \Exception
     */
    public function restore(string $importResultId): bool
    {
        // prepare import result
        $importResult = $this->getEntityManager()->getEntity('ImportResult', $importResultId);

        if (!$importResult->get('isRestored')) {
            // set importResult as restored
            $importResult->set('isRestored', true);
            $this->getEntityManager()->saveEntity($importResult);

            // get logs count
            $logCount = $this
                ->getEntityManager()
                ->getRepository('ImportResultLog')
                ->where([
                    'importResultId' => $importResultId,
                    'type' => ['create', 'update']
                ])
                ->count();

            // run queue manager for data restore
            if ($logCount > 0) {
                // prepare import feed
                $importFeed = $importResult->get('importFeed');

                // prepare limit
                $limit = $importFeed->get('limit');

                // prepare data
                $data = [
                    'offset' => 0,
                    'limit' => $limit,
                    'importResultId' => $importResultId
                ];

                if ($logCount < $limit) {
                    $this->push($this->getName($importFeed->get('name')), 'ImportRestore', $data);
                } else {
                    $offset = 0;
                    while ($offset < $logCount) {
                        $data["offset"] = $offset;

                        $to = $offset + $limit;
                        $to = ($to > $logCount) ? $logCount : $to;

                        // push
                        $this->push($this->getName($importFeed->get('name'))." [$offset-$to]", 'ImportRestore', $data);

                        // increase
                        $offset += $limit;
                    }
                }
            }
        } else {
            throw new BadRequest($this->translate('currentImportResultHasBeenAlreadyRestored', 'exceptions'));
        }

        return true;
    }

    /**
     * @param string $importFeedName
     *
     * @return string
     */
    protected function getName(string $importFeedName): string
    {
        return $this->translate('Restore', 'labels') . ": <strong>{$importFeedName}</strong>";
    }

    /**
     * @param string $type
     * @param string $importResultId
     *
     * @return int
     */
    protected function getLogCount(string $type, string $importResultId): int
    {
        return $this
            ->getEntityManager()
            ->getRepository('ImportResultLog')
            ->where(['importResultId' => $importResultId, 'type' => $type])
            ->count();
    }

    /**
     * @param string $key
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    protected function translate(string $key, string $label, string $scope = 'ImportResult'): string
    {
        return $this->getInjection('language')->translate($key, $label, $scope);
    }

    /**
     * @param string $name
     * @param string $serviceName
     * @param array  $data
     *
     * @return bool
     */
    protected function push(string $name, string $serviceName, array $data = []): bool
    {
        return $this->getInjection('queueManager')->push($name, $serviceName, $data);
    }
}
