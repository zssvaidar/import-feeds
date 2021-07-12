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
 * Class Currency
 */
class Currency extends FloatValue
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
        $value = $config['default'];
        $currency = $config['defaultCurrency'];

        if (!empty($config['singleColumn'])) {
            if (!empty(!empty($config['column']) && $row[$config['column']] != '')) {
                $parts = explode(' ', $row[$config['column']]);
                if (isset($parts[1])) {
                    $value = $parts[0];
                    $currency = $parts[1];
                }
            }
        } else {
            if (!empty($config['column']) && $row[$config['column']] != '') {
                $value = $row[$config['column']];
            }

            if (!empty($config['columnCurrency']) && $row[$config['columnCurrency']] != '') {
                $currency = $row[$config['columnCurrency']];
            }
        }

        // validate currency
        if (!in_array($currency, $this->getConfig()->get('currencyList', []))) {
            throw new \Exception("Incorrect currency for field '{$config['name']}'");
        }

        if (isset($config['attributeId'])) {
            // prepare input row for attribute
            $inputRow->{$config['name']} = self::prepareFloatValue($value);
            $inputRow->data = (object)['currency' => $currency];
        } else {
            // set values to input row
            $inputRow->{$config['name']} = self::prepareFloatValue($value);
            $inputRow->{$config['name'] . 'Currency'} = $currency;
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
            $restore->{$item['name'] . 'Currency'} = $entity->get($item['name'] . 'Currency');
        }

        parent::prepareValue($restore, $entity, $item);
    }
}