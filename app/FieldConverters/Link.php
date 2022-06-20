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
 *
 * This software is not allowed to be used in Russia and Belarus.
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
        $default = empty($config['default']) ? null : $config['default'];

        if (isset($config['column'])) {
            if (count($config['column']) === 1) {
                $value = $this->getSearchValue($config['column'][0], $config, $row, $default);
            } else {
                $value = [];

                foreach ($config['column'] as $key => $column) {
                    $value[] = $this->getSearchValue($column, $config, $row, $default);
                }
            }

            if ($value !== null) {
                if (isset($config['relEntityName'])) {
                    $entityName = $config['relEntityName'];
                } else {
                    $entityName = $this->getMetadata()->get(['entityDefs', $config['entity'], 'links', $config['name'], 'entity']);
                }

                $values = !is_array($value) ? explode($config['fieldDelimiterForRelation'], $value) : $value;

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

                if ($entityName === 'Asset' && in_array('url', $config['importBy'])) {
                    $where = [];
                }

                if (!empty($where)) {
                    $entity = $this->getEntityManager()->getRepository($entityName)->select(['id'])->where($where)->findOne();
                }

                if (!empty($input)) {
                    $user = $this->container->get('user');
                    $userId = empty($user) ? null : $user->get('id');

                    $input->ownerUserId = $userId;
                    $input->ownerUserName = $userId;
                    $input->assignedUserId = $userId;
                    $input->assignedUserName = $userId;

                    if ($this->isUpdate($config)) {
                        $intermediateValues = [];
                        $intermediateColumn = $config['intermediateColumn'];
                        $intermediateImportBy = $config['intermediateImportBy'];

                        if (count($intermediateColumn) === 1) {
                            $intermediateValues = explode($config['fieldDelimiterForRelation'], $row[$intermediateColumn[0]]);
                        } else {
                            foreach ($intermediateColumn as $column) {
                                $intermediateValues[] = $row[$column];
                            }
                        }

                        foreach ($intermediateImportBy as $key => $field) {
                            if (isset($intermediateValues[$key])) {
                                $input->{$field} = $intermediateValues[$key];
                            }
                        }
                    }

                    try {
                        if (!empty($entity)) {
                            $entity = $this->getService($entityName)->updateEntity($entity->id, $input);
                        } elseif (!empty($config['createIfNotExist'])) {
                            $entity = $this->getService($entityName)->createEntity($input);
                        }
                    } catch (\Throwable $e) {
                        $className = get_class($e);

                        $message = sprintf($this->translate('relatedEntityCreatingFailed', 'exceptions', 'ImportFeed'), $this->translate($entityName, 'scopeNames'));
                        $message .= ' ' . $e->getMessage();

                        throw new $className($message);
                    }

                    // for attribute
                    if ($config['type'] === 'Attribute' && !empty($config['relEntityName']) && !empty($entity)) {
                        $entity = $entity->get('file');
                    }
                }

                if (!empty($entity)) {
                    $value = $entity->get('id');
                } else {
                    if (empty($default) && $this->getMetadata()->get([$config['name'], 'fields', $config['entity']]) == 'link') {
                        throw new BadRequest(sprintf($this->translate('noEntityFound', 'exceptions', 'ImportFeed'), $entityName));
                    }

                    $value = $default;
                }
            }
        } else {
            $value = $default;
        }

        $inputRow->{$config['name'] . 'Id'} = $value;

        if ($config['type'] === 'Attribute') {
            $inputRow->{$config['name']} = $inputRow->{$config['name'] . 'Id'};
        }
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
        $inputRow = new \stdClass();
        $this->convert($inputRow, $configuration, $row);

        $where["{$configuration['name']}Id"] = $inputRow->{"{$configuration['name']}Id"};
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

    /**
     * @param mixed $column
     * @param array $config
     * @param array $row
     * @param mixed $default

     * @return mixed|null
     *
     * @throws \Import\Exceptions\IgnoreAttribute
     */
    protected function getSearchValue($column, array $config, array $row, $default)
    {
        $value = $row[$column] ?? null;
        $this->ignoreAttribute($value, $config);
        if (strtolower((string)$value) === strtolower((string)$config['emptyValue']) || $value === '') {
            $value = $default;
        }
        if (strtolower((string)$value) === strtolower((string)$config['nullValue'])) {
            $value = null;
        }

        return $value;
    }

    /**
     * @return bool
     */
    protected function isUpdate(array $config): bool
    {
        return !empty($config['intermediateImportBy']) && !empty($config['intermediateColumn']);
    }
}
