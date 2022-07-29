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
    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        $ids = [];

        if (count($config['column']) == 1) {
            $value = $row[$config['column'][0]];

            if (strtolower((string)$value) === strtolower((string)$config['emptyValue']) || $value === '') {
                $value = null;
            }

            if (strtolower((string)$value) === strtolower((string)$config['nullValue'])) {
                $value = null;
            }

            if ($value !== null) {
                $items = explode($config['delimiter'], $value);

                foreach ($items as $item) {
                    if (empty($item)) {
                        continue 1;
                    }

                    $id = $this->convertItem($config, ['column' => [0]], [$item]);
                    if ($id !== null) {
                        $ids[$id] = $id;
                    }

                }
            }
        } else {
            $rows = [];
            $columns = [];

            foreach ($config['column'] as $key => $column) {
                $value = explode($config['delimiter'], $row[$column]);

                if (count($value) > 1) {
                    if (isset($config['relEntityName'])) {
                        $entityName = $config['relEntityName'];
                    } else {
                        $entityName = $this->getMetadata()->get(['entityDefs', $config['entity'], 'links', $config['name'], 'entity']);
                    }

                    throw new BadRequest(sprintf($this->translate('listSeparatorNotAllowed', 'exceptions', 'ImportFeed'), $entityName));
                }

                $rows[$key] = $value[0];
                $columns[] = $key;
            }

            $id = $this->convertItem($config, ['column' => $columns], $rows);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }

        if (empty($ids) && !empty($config['default'])) {
            $ids = Json::decode($config['default'], true);
        }

        $ids = array_values($ids);;

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
}