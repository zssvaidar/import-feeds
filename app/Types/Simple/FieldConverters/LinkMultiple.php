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

namespace Import\Types\Simple\FieldConverters;

use Espo\Core\Exceptions\BadRequest;
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
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
        // prepare ids
        $ids = explode(',', $config['default']);

        // prepare names
        $names = isset($config['defaultNames']) ? array_values($config['defaultNames']) : [];

        if (!empty($config['column'])) {
            $entityName = $this->container->get('metadata')->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);
            $ids = [];
            $names = [];

            foreach ($config['column'] as $column) {
                $items = explode($delimiter, $row[$column]);
                foreach ($items as $item) {
                    $values = explode('|', $item);
                    $where = [];
                    foreach ($config['field'] as $k => $field) {
                        $where[$field] = $values[$k];
                    }

                    $entity = $this->getEntityManager()
                        ->getRepository($entityName)
                        ->select(['id', 'name'])
                        ->where($where)
                        ->findOne();

                    if (empty($entity)) {
                        if (empty($config['createIfNotExist'])) {
                            throw new BadRequest("No related entity found.");
                        }

                        $post = Json::decode(Json::encode($where));
                        $entity = $this->getService($entityName)->createEntity($post);
                    }

                    $ids[$entity->get('id')] = $entity->get('id');
                    $names[$entity->get('id')] = $entity->get('name');
                }
            }
        }

        $inputRow->{$config['name'] . 'Ids'} = array_values($ids);
        $inputRow->{$config['name'] . 'Names'} = array_values($names);
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
}