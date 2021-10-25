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

namespace Import\FieldConverters;

use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

/**
 * Class LinkMultiple
 */
class LinkMultiple extends Asset
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        $entityType = $config['entity'];
        $delimiter = $config['delimiter'];

        if (!empty($config['column'])) {
            $entityName = $this->container->get('metadata')->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);
            $ids = [];

            foreach ($config['column'] as $column) {
                $items = explode($delimiter, $row[$column]);
                if (empty($items)) {
                    continue 1;
                }

                foreach ($items as $item) {
                    $values = explode('|', $item);

                    $where = [];
                    foreach ($config['importBy'] as $k => $field) {
                        $where[$field] = $values[$k];
                    }

                    $entity = null;
                    if (!empty($where)) {
                        $entity = $this->getEntityManager()
                            ->getRepository($entityName)
                            ->select(['id', 'name'])
                            ->where($where)
                            ->findOne();
                    }

                    if (empty($entity)) {
                        if (!empty($config['createIfNotExist'])) {
                            $entity = $this->getEntityManager()->getRepository($entityName)->get();
                            $entity->set($where);
                            $this->getEntityManager()->saveEntity($entity);
                        }
                    }

                    if (!empty($entity)) {
                        $ids[$entity->get('id')] = $entity->get('id');
                    }
                }
            }
        }

        if (empty($ids) && !empty($config['default'])) {
            $ids = Json::decode($config['default'], true);
        }

        if (!empty($ids)) {
            $inputRow->{$config['name'] . 'Ids'} = array_values($ids);
            $inputRow->{$config['name'] . 'Names'} = array_values($ids);
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        $ids = null;
        $names = null;
        $foreigners = $entity->get($item['name'])->toArray();

        if (count($foreigners) > 0) {
            $ids = array_column($foreigners, 'id');
            $names = array_column($foreigners, 'id');
        }

        $restore->{$item['name'] . 'Ids'} = $ids;
        $restore->{$item['name'] . 'Names'} = $names;
    }

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
        if ($entity->has('defaultIds')) {
            $entity->set('default', empty($entity->get('defaultIds')) ? null : Json::encode($entity->get('defaultIds')));
        }
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
    {
        $entity->set('defaultIds', null);
        $entity->set('defaultNames', null);
        if (!empty($entity->get('default'))) {
            $relEntityName = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'links', $entity->get('name'), 'entity']);
            if (!empty($relEntityName)) {
                $entity->set('defaultIds', Json::decode($entity->get('default'), true));
                $names = [];
                foreach ($entity->get('defaultIds') as $id) {
                    $relEntity = $this->getEntityManager()->getEntity($relEntityName, $id);
                    $names[$id] = empty($relEntity) ? $id : $relEntity->get('name');
                }
                $entity->set('defaultNames', $names);
            }
        }
    }
}