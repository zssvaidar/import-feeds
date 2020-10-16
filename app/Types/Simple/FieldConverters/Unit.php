<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
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
        // prepare values
        $value = (!empty($config['column']) && $row[$config['column']] != '') ? $row[$config['column']] : $config['default'];
        $unit = (!empty($config['columnUnit']) && $row[$config['columnUnit']] != '') ? $row[$config['columnUnit']] : $config['defaultUnit'];

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
        $restore->{$item['name'] . 'Unit'} = $entity->get($item['name'] . 'Unit');

        parent::prepareValue($restore, $entity, $item);
    }

    /**
     * @param string $unit
     * @param string $entityType
     * @param array $config
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
     * @param array $config
     *
     * @return string
     */
    protected function getMeasure(string $entityType, array $config): string
    {
        return (string)$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $config['name'], 'measure']);
    }
}