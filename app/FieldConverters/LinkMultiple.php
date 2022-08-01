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
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

class LinkMultiple extends Varchar
{
    /**
     * @var string
     */
    protected string $relationEntityName;

    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        $ids = [];

        $this->relationEntityName = $config['relEntityName'] ?? $this->getMetadata()->get(['entityDefs', $config['entity'], 'links', $config['name'], 'entity']);

        $createIfNotExist = $config['createIfNotExist'];
        $config['createIfNotExist'] = false;

        foreach ($this->prepareItem($config, $config['column'], $row) as $item) {
            $id = $this->convertItem($config, ['column' => array_keys($item)], $item);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }

        if (empty($ids)) {
            if (!empty($config['foreignColumn']) && !empty($config['foreignImportBy']) && !empty($createIfNotExist)) {
                $data = $this->prepareItem($config, $config['foreignColumn'], $row);

                if (count($data) > 0) {
                    $user = $this->container->get('user');
                    $userId = empty($user) ? null : $user->get('id');

                    foreach ($data as $item) {
                        if (count($config['foreignColumn']) == 1) {
                            $item = explode($config['fieldDelimiterForRelation'], $item[0]);
                        }

                        $input = new \stdClass();

                        $input->ownerUserId = $userId;
                        $input->ownerUserName = $userId;
                        $input->assignedUserId = $userId;
                        $input->assignedUserName = $userId;

                        foreach ($config['foreignImportBy'] as $key => $field) {
                            if (isset($item[$key])) {
                                $input->{$field} = $item[$key];
                            }
                        }

                        try {
                            $entity = $this->getService($this->relationEntityName)->createEntity($input);

                            $ids[] = $entity->id;
                        } catch (\Throwable $e) {
                            $className = get_class($e);

                            $message = sprintf($this->translate('relatedEntityCreatingFailed', 'exceptions', 'ImportFeed'), $this->translate($this->relationEntityName, 'scopeNames'));
                            $message .= ' ' . $e->getMessage();

                            throw new $className($message);
                        }
                    }
                }
            } elseif (!empty($config['default'])) {
                $ids = Json::decode($config['default'], true);
            }
        }

        $ids = array_values($ids);

        $fieldName = $config['name'] . 'Ids';

        if (!empty($inputRow->$fieldName)) {
            $inputRow->$fieldName = array_merge($inputRow->$fieldName, $ids);
        } else {
            $inputRow->$fieldName = $ids;
        }

        if ($config['type'] === 'Attribute') {
            $inputRow->{$config['name']} = $inputRow->$fieldName;
        }

        if (empty($config['replaceRelation'])) {
            $inputRow->{$config['name'] . 'AddOnlyMode'} = 1;
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

    /**
     * @param array $config
     * @param array $column
     * @param array $row
     *
     * @return string|null
     */
    protected function convertItem(array $config, array $column, array $row): ?string
    {
        $input = new \stdClass();
        $this
            ->getService('ImportConfiguratorItem')
            ->getFieldConverter('link')
            ->convert($input, array_merge($config, $column, ['default' => null]), $row);

        $key = $config['name'] . 'Id';
        if (property_exists($input, $key) && $input->$key !== null) {
            return $input->$key;
        }

        return null;
    }

    /**
     * @param array $config
     * @param array $columns
     * @param array $row
     *
     * @return array
     *
     * @throws BadRequest
     */
    protected function prepareItem(array $config, array $columns, array $row): array
    {
        $result = [];

        if (count($columns) == 1) {
            $value = $row[$columns[0]];

            if (strtolower((string)$value) === strtolower((string)$config['emptyValue']) || $value === '') {
                $value = null;
            }

            if (strtolower((string)$value) === strtolower((string)$config['nullValue'])) {
                $value = null;
            }

            if ($value !== null) {
                $values = explode($config['delimiter'], $value);

                foreach ($values as $value) {
                    $result[] = [$value];
                }
            }
        } else {
            $rows = [];

            foreach ($columns as $column) {
                $value = explode($config['delimiter'], $row[$column]);

                if (count($value) > 1) {
                    throw new BadRequest(sprintf($this->translate('listSeparatorNotAllowed', 'exceptions', 'ImportFeed'), $this->relationEntityName));
                }

                $rows[] = $value[0];
            }

            $result[] = $rows;
        }

        return $result;
    }
}