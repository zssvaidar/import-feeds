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

use Espo\ORM\Entity;

/**
 * Class LinkMultiple
 */
class LinkMultiple extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        // prepare ids
        $ids = explode(',', $config['default']);

        // prepare names
        $names = isset($config['defaultNames']) ? array_values($config['defaultNames']) : [];

        if (!is_null($config['column']) && !empty($row[$config['column']])) {
            $ids = explode($delimiter, $row[$config['column']]);

            // get entity name
            $entityName = $this
                ->container
                ->get('metadata')
                ->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);

            if (!empty($entityName)) {
                // find entity
                $entities = $this
                    ->container
                    ->get('entityManager')
                    ->getRepository($entityName)
                    ->select(['id', 'name'])
                    ->where([$config['field'] => $ids])
                    ->find()
                    ->toArray();

                if (count($entities) > 0) {
                    $ids = array_column($entities, 'id');
                    $names = array_column($entities, 'name');
                }
            }
        }

        $inputRow->{$config['name'] . 'Ids'} = (array)$ids;
        $inputRow->{$config['name'] . 'Names'} = $names;
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
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