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
 * Class Unit
 */
class Unit extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        if (isset($config['attributeId'])) {
            $this->convertAttribute($inputRow, $entityType, $config, $row, $delimiter);
            return;
        }

        $value = $config['default'];
        $unit = $config['defaultUnit'];

        if (!empty($config['singleColumn'])) {
            if (!empty(!empty($config['column']) && $row[$config['column']] != '')) {
                $parts = explode(' ', $row[$config['column']]);
                if (isset($parts[1])) {
                    $value = $parts[0];
                    $unit = $parts[1];
                }
            }
        } else {
            if (!empty($config['column']) && $row[$config['column']] != '') {
                $value = $row[$config['column']];
            }

            if (!empty($config['columnUnit']) && $row[$config['columnUnit']] != '') {
                $unit = $row[$config['columnUnit']];
            }
        }

        // validate unit float value
        if (!is_null($value) && filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new \Exception("Incorrect value for field '{$config['name']}'");
        }

        // validate measuring unit
        if (!$this->validateUnit($unit, $entityType, $config)) {
            throw new \Exception("Incorrect measuring unit for field '{$config['name']}'");
        }

        // set values to input row
        $inputRow->{$config['name']} = (float)$value;
        $inputRow->{$config['name'] . 'Unit'} = $unit;
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        if (isset($item['attributeId'])) {
            $restore->data = $entity->get('data');
        } else {
            $restore->{$item['name'] . 'Unit'} = $entity->get($item['name'] . 'Unit');
        }

        parent::prepareValue($restore, $entity, $item);
    }

    /**
     * @param string $unit
     * @param string $entityType
     * @param array  $config
     *
     * @return bool
     */
    protected function validateUnit(string $unit, string $entityType, array $config): bool
    {
        // prepare result
        $result = false;
        // prepare exist measuring units list
        $units = $this->getConfig()->get('unitsOfMeasure', []);

        // prepare measure
        $measure = $this->getMeasure($entityType, $config);

        // check for exist unit
        foreach ($units as $name => $data) {
            if (in_array($unit, $data->unitList) && $name == $measure) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $entityType
     * @param array  $config
     *
     * @return string
     */
    protected function getMeasure(string $entityType, array $config): string
    {
        if (!isset($config['attributeId'])) {
            return (string)$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $config['name'], 'measure']);
        } else {
            return $config['attribute']->get('typeValue')[0];
        }
    }

    protected function convertAttribute(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
        // prepare values
        $value = (!empty($config['column']) && $row[$config['column']] != '') ? $row[$config['column']] : $config['default'];
        $unit = (!empty($config['columnUnit']) && $row[$config['columnUnit']] != '') ? $row[$config['columnUnit']] : $config['defaultUnit'];

        // validate unit float value
        if (!is_null($value) && filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new \Exception("Incorrect value for attribute '{$config['attribute']->get('name')}'");
        }

        // validate measuring unit
        if (!$this->validateUnit($unit, $entityType, $config)) {
            throw new \Exception("Incorrect measuring unit for attribute '{$config['attribute']->get('name')}'");
        }

        // prepare input row for attribute
        $inputRow->{$config['name']} = (float)$value;
        $inputRow->data = (object)['unit' => $unit];
    }
}