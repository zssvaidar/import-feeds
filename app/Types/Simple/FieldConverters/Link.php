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
 * Class Link
 */
class Link extends AbstractConverter
{
    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
        // prepare default entity id
        $value = $config['default'];

        // prepare default entity name
        $name = isset($config['defaultName']) ? $config['defaultName'] : null;

        if (!empty($row[$config['column']])) {
            // get entity name
            $entityName = $this->getMetadata()->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);

            $values = explode('|', $row[$config['column']]);
            $where = [];
            foreach ($config['field'] as $k => $field) {
                $where[$field] = $values[$k];
            }

            $entity = $this->getEntityManager()
                ->getRepository($entityName)
                ->select(['id', 'name'])
                ->where($where)
                ->findOne();

            if (!empty($entity)) {
                $value = $entity->get('id');
                $name = $entity->get('name');
            } else {
                throw new \Exception("Not found any entities for field '{$config['name']}'");
            }
        }

        $inputRow->{$config['name'] . 'Id'} = $value;
        $inputRow->{$config['name'] . 'Name'} = $name;
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
}
