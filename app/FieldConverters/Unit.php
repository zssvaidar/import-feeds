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

use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

/**
 * Class Unit
 */
class Unit extends FloatValue
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
        $value = $config['default'];
        $unit = $config['defaultUnit'];

        $isSingleColumn = !isset($config['column'][1]);

        if ($isSingleColumn) {
            if (!empty(!empty($config['column'][0]) && $row[$config['column'][0]] != '')) {
                $parts = explode(' ', $row[$config['column'][0]]);
                $value = $parts[0];
                if (isset($parts[1])) {
                    $unit = $parts[1];
                }
            }
        } else {
            if (!empty($config['column'][0]) && $row[$config['column'][0]] != '') {
                $value = $row[$config['column'][0]];
            }

            if (!empty($config['column'][1]) && $row[$config['column'][1]] != '') {
                $unit = $row[$config['column'][1]];
            }
        }

        // validate measuring unit
        if (!$this->validateUnit($unit, $entityType, $config)) {
            throw new \Exception("Incorrect measuring unit for field '{$config['name']}'");
        }

        if (isset($config['attributeId'])) {
            // prepare input row for attribute
            $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
            $inputRow->data = (object)['unit' => $unit];
        } else {
            // set values to input row
            $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
            $inputRow->{$config['name'] . 'Unit'} = $unit;
        }
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        if (isset($item['attributeId'])) {
            $restore->data = $entity->get('data');
        } else {
            $restore->{$item['name'] . 'Unit'} = $entity->get($item['name'] . 'Unit');
        }

        parent::prepareValue($restore, $entity, $item);
    }

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
        if (!empty($configuration['default'])) {
            $default = Json::decode($configuration['default'], true);
            if ((!empty($default['value']) || $default['value'] === '0' || $default['value'] === 0) && !empty($default['unit'])) {
                $value = (float)$default['value'];
                $unit = (string)$default['unit'];
            }
        }

        if (isset($configuration['column'][1])) {
            $value = (float)$row[$configuration['column'][0]];
            $unit = (string)$row[$configuration['column'][1]];
        } elseif (isset($configuration['column'][0])) {
            $parts = explode(' ', $row[$configuration['column'][0]]);
            $value = (float)array_shift($parts);
            $unit = (string)array_shift($parts);
        }

        if (isset($value) && isset($unit)) {
            $where[$configuration['name']] = $value;
            $where["{$configuration['name']}Unit"] = $unit;
        }
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
        $old = !$entity->isNew() ? Json::decode($entity->getFetched('default'), true) : ['value' => 0, 'unit' => ''];
        $unitData = [
            'value' => $entity->has('default') && strpos((string)$entity->get('default'), '{') === false ? $entity->get('default') : $old['value'],
            'unit'  => $entity->has('defaultUnit') ? $entity->get('defaultUnit') : $old['unit']
        ];

        $entity->set('default', Json::encode($unitData));
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
    {
        $unitData = Json::decode($entity->get('default'), true);
        $entity->set('default', $unitData['value']);
        $entity->set('defaultUnit', $unitData['unit']);
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
}