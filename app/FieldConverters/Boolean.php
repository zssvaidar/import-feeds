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
 * Class Boolean
 */
class Boolean extends Varchar
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        $result = (isset($config['column'][0]) && ($row[$config['column'][0]]) != '') ? $row[$config['column'][0]] : $config['default'];

        if (is_null(filter_var($result, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
            throw new \Exception("Incorrect value for field '{$config['name']}'");
        }

        $inputRow->{$config['name']} = (bool)$result;
    }

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
        $value = !empty($configuration['default']);

        if (isset($configuration['column'][0]) && isset($row[$configuration['column'][0]])) {
            $value = !empty($row[$configuration['column'][0]]);
        }

        $where[$configuration['name']] = $value;
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
        $entity->set('default', !empty($entity->get('default')));
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
    {
        $entity->set('default', !empty($entity->get('default')));
    }
}
