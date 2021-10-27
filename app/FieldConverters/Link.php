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

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;

class Link extends Varchar
{
    const ALLOWED_TYPES = ['bool', 'enum', 'varchar', 'float', 'int', 'text', 'wysiwyg'];

    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        if (empty($config['default'])) {
            $config['default'] = null;
        }

        if (isset($config['column'][0]) && isset($row[$config['column'][0]])) {
            $value = $row[$config['column'][0]];
            if ($value === $config['emptyValue'] || $value === '') {
                $value = $config['default'];
            }
            if ($value === $config['nullValue']) {
                $value = null;
            }

            if ($value !== null) {
                if (isset($config['relEntityName'])) {
                    $entityName = $config['relEntityName'];
                } else {
                    $entityName = $this->getMetadata()->get(['entityDefs', $config['entity'], 'links', $config['name'], 'entity']);
                }

                $user = $this->container->get('user');
                $userId = empty($user) ? null : $user->get('id');

                $values = explode('|', $value);

                $input = new \stdClass();

                $where = [];

                foreach ($config['importBy'] as $k => $field) {
                    $fieldData = $this->getMetadata()->get(['entityDefs', $entityName, 'fields', $field], ['type' => 'varchar']);

                    if (empty($fieldData['type']) || !in_array($fieldData['type'], self::ALLOWED_TYPES)) {
                        continue 1;
                    }

                    if (!isset($values[$k])) {
                        throw new BadRequest(sprintf($this->translate('wrongImportByValuesCount', 'exceptions', 'ImportFeed'), $entityName));
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
                    $input->ownerUserId = $userId;
                    $input->ownerUserName = $userId;
                    $input->assignedUserId = $userId;
                    $input->assignedUserName = $userId;
                    $entity = $this->getService($entityName)->createEntity($input);

                    // for attribute
                    if (!empty($config['relEntityName']) && !empty($entity)) {
                        $entity = $entity->get('file');
                    }
                }

                if (!empty($entity)) {
                    $value = $entity->get('id');
                } else {
                    $value = $config['default'];
                }
            }
        } else {
            $value = $config['default'];
        }

        $inputRow->{$config['name'] . 'Id'} = $value;
        $inputRow->{$config['name'] . 'Name'} = $value;
    }

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
