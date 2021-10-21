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

        $isSingleColumn = !isset($config['column'][1]);

        if ($isSingleColumn) {
            if (!empty($config['column'][0]) && $row[$config['column'][0]] != '') {
                $parts = explode(' ', $row[$config['column'][0]]);
                $value = $parts[0];
                if (isset($parts[1])) {
                    $currency = $parts[1];
                }
            }
        } else {
            if (!empty($config['column'][0]) && $row[$config['column'][0]] != '') {
                $value = $row[$config['column'][0]];
            }

            if (!empty($config['column'][1]) && $row[$config['column'][1]] != '') {
                $currency = $row[$config['column'][1]];
            }
        }

        // validate currency
        if (!in_array($currency, $this->getConfig()->get('currencyList', []))) {
            throw new \Exception("Incorrect currency for field '{$config['name']}'");
        }

        if (isset($config['attributeId'])) {
            // prepare input row for attribute
            $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
            $inputRow->data = (object)['currency' => $currency];
        } else {
            // set values to input row
            $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
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

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
        echo '<pre>';
        print_r($configuration);
        die();
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
        $old = !$entity->isNew() ? Json::decode($entity->getFetched('default'), true) : ['value' => 0, 'currency' => 'EUR'];
        $currencyData = [
            'value'    => $entity->has('default') && strpos((string)$entity->get('default'), '{') === false ? $entity->get('default') : $old['value'],
            'currency' => $entity->has('defaultCurrency') ? $entity->get('defaultCurrency') : $old['currency']
        ];

        $entity->set('default', Json::encode($currencyData));
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
    {
        $currencyData = Json::decode($entity->get('default'), true);
        $entity->set('default', $currencyData['value']);
        $entity->set('defaultCurrency', $currencyData['currency']);
    }
}