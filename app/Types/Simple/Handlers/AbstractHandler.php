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
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var array
     */
    protected $restore = [];

    /**
     * AbstractHandler constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $fileData
     * @param array $data
     *
     * @return bool
     */
    abstract public function run(array $fileData, array $data): bool;

    /**
     * @param array  $configuration
     * @param string $idField
     *
     * @return array|null
     */
    protected function getIdRow(array $configuration, string $idField): ?array
    {
        foreach ($configuration as $row) {
            if ($row['name'] == $idField) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param string $entityType
     * @param string $name
     * @param array  $ids
     *
     * @return mixed
     */
    protected function getExists(string $entityType, string $name, array $ids): array
    {
        $select = ($name == 'id') ? [$name] : ['id', $name];

        // get data
        $data = $this
            ->getEntityManager()
            ->getRepository($entityType)
            ->select($select)
            ->where([$name => $ids])
            ->find();

        $result = [];

        if (count($data) > 0) {
            foreach ($data as $entity) {
                $result[$entity->get($name)] = $entity->get('id');
            }
        }

        return $result;
    }

    /**
     * @param \stdClass $inputRow
     * @param string    $entityType
     * @param array     $item
     * @param array     $row
     * @param string    $delimiter
     */
    protected function convertItem(\stdClass $inputRow, string $entityType, array $item, array $row, string $delimiter)
    {
        // get converter
        $converter = $this
            ->getMetadata()
            ->get(['import', 'simple', 'fields', $this->getType($entityType, $item), 'converter']);

        // delegate
        if (!empty($converter)) {
            return (new $converter($this->container))->convert($inputRow, $entityType, $item, $row, $delimiter);
        }

        // prepare value
        if (is_null($item['column']) || $row[$item['column']] == '') {
            $value = $item['default'];
            if (!empty($value) && is_string($value)) {
                $value = str_replace("{{hash}}", Util::generateId(), $value);
            }
        } else {
            $value = $row[$item['column']];
        }

        // set
        $inputRow->{$item['name']} = $value;
    }

    /**
     * @param \stdClass $restore
     * @param Entity    $entity
     * @param array     $item
     */
    protected function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        // get converter
        $converter = $this
            ->getMetadata()
            ->get(['import', 'simple', 'fields', $this->getType($entity->getEntityType(), $item), 'converter']);

        // delegate
        if (!empty($converter)) {
            return (new $converter($this->container))->prepareValue($restore, $entity, $item);
        }

        $restore->{$item['name']} = $entity->get($item['name']);
    }

    /**
     * @param string $entityType
     * @param array  $item
     *
     * @return string|null
     */
    protected function getType(string $entityType, array $item): ?string
    {
        return (string)$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $item['name'], 'type']);
    }

    /**
     * @param string $entityName
     * @param string $importResultId
     * @param string $type
     * @param string $row
     * @param string $data
     *
     * @return Entity
     */
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

    /**
     * @param string $action
     * @param string $entityType
     * @param        $data
     */
    protected function saveRestoreRow(string $action, string $entityType, $data)
    {
        $this->restore[] = [
            'action' => $action,
            'entity' => $entityType,
            'data'   => $data
        ];
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    /**
     * @param string $entityType
     *
     * @return ServiceFactory
     */
    protected function getServiceFactory(): ServiceFactory
    {
        return $this->container->get('serviceFactory');
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }
}
