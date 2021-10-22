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

use Espo\ORM\Entity;

/**
 * Class Link
 */
class Link extends Varchar
{
    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        if (!empty($row[$config['column'][0]])) {
            // get entity name
            $entityName = $this->getMetadata()->get(['entityDefs', $config['entity'], 'links', $config['name'], 'entity']);

            $values = explode('|', $row[$config['column'][0]]);
            $where = [];
            foreach ($config['importBy'] as $k => $field) {
                $where[$field] = $values[$k];
            }

            $entity = $this->getEntityManager()
                ->getRepository($entityName)
                ->select(['id', 'name'])
                ->where($where)
                ->findOne();

            if (!empty($entity)) {
                $value = $entity->get('id');
            } else {
                if (!empty($config['createIfNotExist'])) {
                    $entity = $this->getEntityManager()->getRepository($entityName)->get();
                    $entity->set($where);
                    $this->getEntityManager()->saveEntity($entity);
                    $value = $entity->get('id');
                }
            }
        }

        if (empty($value) && !empty($config['default'])) {
            $value = $config['default'];
        }

        if (!empty($value)) {
            $inputRow->{$config['name'] . 'Id'} = $value;
            $inputRow->{$config['name'] . 'Name'} = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        $value = null;

        if (!empty($foreign = $entity->get($item['name']))) {
            $value = $foreign->get('id');
        }

        $restore->{$item['name'] . 'Id'} = $value;
    }

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
        $value = $configuration['default'];
        if (isset($configuration['column'][0]) && isset($row[$configuration['column'][0]])) {
            $relEntityType = $this->getMetadata()->get(['entityDefs', $configuration['entity'], 'links', $configuration['name'], 'entity']);
            if (!empty($relEntityType)) {
                $parts = explode($configuration['delimiter'], $row[$configuration['column'][0]]);

                $relWhere = [];
                foreach ($configuration['importBy'] as $k => $v) {
                    $relWhere[$v] = isset($parts[$k]) ? $parts[$k] : null;
                }

                $relEntity = $this
                    ->getEntityManager()
                    ->getRepository($relEntityType)
                    ->select(['id'])
                    ->where($relWhere)
                    ->findOne();

                if (!empty($relEntity)) {
                    $value = $relEntity->get('id');
                }
            }
        }
        $where["{$configuration['name']}Id"] = $value;
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
        if ($entity->has('defaultId')) {
            $entity->set('default', empty($entity->get('defaultId')) ? null : $entity->get('defaultId'));
        }
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
    {
        $entity->set('defaultId', null);
        $entity->set('defaultName', null);
        if (!empty($entity->get('default'))) {
            $relEntityName = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'links', $entity->get('name'), 'entity']);
            if (!empty($relEntityName)) {
                $entity->set('defaultId', $entity->get('default'));
                $relEntity = $this->getEntityManager()->getEntity($relEntityName, $entity->get('defaultId'));
                $entity->set('defaultName', empty($relEntity) ? $entity->get('defaultId') : $relEntity->get('name'));
            }
        }
    }
}
