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

class LinkMultiple extends Varchar
{
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

                    $input = new \stdClass();

                    $where = [];

                    foreach ($config['importBy'] as $k => $field) {
                        $fieldData = $this->getMetadata()->get(['entityDefs', $entityName, 'fields', $field]);

                        if (empty($fieldData['type']) || !in_array($fieldData['type'], Link::ALLOWED_TYPES)) {
                            continue 1;
                        }

                        $this
                            ->getService('ImportConfiguratorItem')
                            ->getFieldConverter($fieldData['type'])
                            ->convert($input, ['name' => $field, 'column' => [0], 'default' => null], [$values[$k]]);

                        if (empty($fieldData['notStorable'])) {
                            $where[$field] = $values[$k];
                        }
                    }

                    $entity = null;

                    if (!empty($where)) {
                        $entity = $this->getEntityManager()->getRepository($entityName)->select(['id'])->where($where)->findOne();
                    }

                    if (empty($entity) && !empty($input) && !empty($config['createIfNotExist'])) {
                        $entity = $this->getService($entityName)->createEntity($input);
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