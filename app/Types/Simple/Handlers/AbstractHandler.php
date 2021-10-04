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

namespace Import\Types\Simple\Handlers;

use Espo\Core\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Treo\Core\ServiceFactory;

/**
 * Class AbstractHandler
 */
abstract class AbstractHandler
{
    protected Container $container;

    protected array $restore = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function run(array $fileData, array $data): bool;

    protected function findExistEntity(string $entityType, array $configuration, array $row): ?Entity
    {
        if (empty($configuration['idField']) || empty($configuration['configuration'])) {
            return null;
        }

        if (is_string($configuration['idField'])) {
            $configuration['idField'] = (array)$configuration['idField'];
        }

        $where = [];
        foreach ($configuration['idField'] as $idField) {
            foreach ($configuration['configuration'] as $item) {
                if ($item['name'] === $idField) {
                    $value = $item['default'];
                    if (isset($item['column'][0])) {
                        $value = $item['column'][0];
                    }

                    if (!empty($item['isLink'])) {
                        $idField = $idField . 'Id';
                    }

                    $where[$idField] = $value;
                }
            }
        }

        if (empty($where)) {
            return null;
        }

        return $this->getEntityManager()->getRepository($entityType)->where($where)->findOne();
    }

    protected function convertItem(\stdClass $inputRow, string $entityType, array $item, array $row, string $delimiter): void
    {
        // get converter
        $converter = $this->getMetadata()->get(['import', 'simple', 'fields', $this->getType($entityType, $item), 'converter']);

        // delegate
        if (!empty($converter)) {
            (new $converter($this->container))->convert($inputRow, $entityType, $item, $row, $delimiter);
            return;
        }

        // prepare value
        if (is_null($item['column'][0]) || $row[$item['column'][0]] == '') {
            $value = $item['default'];
            if (!empty($value) && is_string($value)) {
                $value = str_replace("{{hash}}", Util::generateId(), $value);
            }
        } else {
            $value = $row[$item['column'][0]];
        }

        // set
        $inputRow->{$item['name']} = $value;
    }

    protected function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        // get converter
        $converter = $this
            ->getMetadata()
            ->get(['import', 'simple', 'fields', $this->getType($entity->getEntityType(), $item), 'converter']);

        // delegate
        if (!empty($converter)) {
            (new $converter($this->container))->prepareValue($restore, $entity, $item);
            return;
        }

        $restore->{$item['name']} = $entity->get($item['name']);
    }

    protected function getType(string $entityType, array $item): ?string
    {
        return (string)$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item['name'], 'type']);
    }

    public function log(string $entityName, string $importResultId, string $type, string $row, string $data): Entity
    {
        // create log
        $log = $this->getEntityManager()->getEntity('ImportResultLog');
        $log->set('name', $row);
        $log->set('rowNumber', $row);
        $log->set('entityName', $entityName);
        $log->set('importResultId', $importResultId);
        $log->set('type', $type);
        if ($type == 'error') {
            $log->set('message', $data);
        } else {
            $log->set('entityId', $data);
            $log->set('restoreData', $this->restore);
        }

        $this->getEntityManager()->saveEntity($log);

        $this->restore = [];

        return $log;
    }

    protected function saveRestoreRow(string $action, string $entityType, $data): void
    {
        $this->restore[] = [
            'action' => $action,
            'entity' => $entityType,
            'data'   => $data
        ];
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function getServiceFactory(): ServiceFactory
    {
        return $this->container->get('serviceFactory');
    }

    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }
}
