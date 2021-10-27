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

use Espo\Core\Exceptions\BadRequest;
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
    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        $parsedDefault = $this->parseDefault($config);

        $value = $parsedDefault[0];
        $currency = $parsedDefault[1];

        $isSingleColumn = !isset($config['column'][1]);

        if ($isSingleColumn) {
            if (!empty($config['column'][0]) && $row[$config['column'][0]] != '') {
                $parts = explode(' ', preg_replace('!\s+!', ' ', trim($row[$config['column'][0]])));

                if (count($parts) > 2) {
                    throw new BadRequest($this->translate('incorrectCurrencyValue', 'exceptions', 'ImportFeed'));
                }

                $value = $parts[0];
                if (isset($parts[1])) {
                    $currency = $parts[1];
                }
            }
        } else {
            if (!empty($config['column'][0]) && $row[$config['column'][0]] != '') {
                $value = trim($row[$config['column'][0]]);
            }

            if (!empty($config['column'][1]) && $row[$config['column'][1]] != '') {
                $currency = trim($row[$config['column'][1]]);
            }
        }

        // validate currency
        if (!in_array($currency, $this->getConfig()->get('currencyList', []))) {
            if (isset($config['attributeId'])) {
                $attribute = $this->getEntityManager()->getEntity('Attribute', $config['attributeId']);
                $fieldValue = empty($attribute) ? '-' : $attribute->get('name');
                $message = sprintf($language->translate('incorrectAttributeCurrency', 'exceptions', 'ImportFeed'), $currency, $fieldValue);
            } else {
                $message = sprintf($language->translate('incorrectCurrency', 'exceptions', 'ImportFeed'), $currency, $config['name']);
            }
            throw new BadRequest($message);
        }

        if ($value !== null) {
            if (isset($config['attributeId'])) {
                $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
                $inputRow->data = (object)['currency' => $currency];
            } else {
                $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
                $inputRow->{$config['name'] . 'Currency'} = $currency;
            }
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
        $parsedDefault = $this->parseDefault($configuration);

        $value = $parsedDefault[0];
        $currency = $parsedDefault[1];

        if (isset($configuration['column'][1])) {
            $value = (float)$row[$configuration['column'][0]];
            $currency = (string)$row[$configuration['column'][1]];
        } elseif (isset($configuration['column'][0])) {
            $parts = explode(' ', $row[$configuration['column'][0]]);
            $value = (float)array_shift($parts);
            $currency = (string)array_shift($parts);
        }

        if (isset($value) && isset($currency)) {
            $where[$configuration['name']] = $value;
            $where["{$configuration['name']}Currency"] = $currency;
        }
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

    protected function parseDefault(array $configuration): array
    {
        $value = null;
        $currency = null;

        if (!empty($configuration['default'])) {
            $default = Json::decode($configuration['default'], true);
            if ((!empty($default['value']) || $default['value'] === '0' || $default['value'] === 0) && !empty($default['currency'])) {
                $value = (float)$default['value'];
                $currency = (string)$default['currency'];
            }
        }

        return [$value, $currency];
    }
}