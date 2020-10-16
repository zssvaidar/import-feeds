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
 * Class Currency
 */
class Currency extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        // prepare values
        $value = (!empty($config['column']) && $row[$config['column']] != '') ? $row[$config['column']] : $config['default'];
        $currency = (!empty($config['columnCurrency']) && $row[$config['columnCurrency']] != '') ? $row[$config['columnCurrency']] : $config['defaultCurrency'];

        // validate currency float value
        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new \Exception("Incorrect value for field '{$config['name']}'");
        }

        // validate currency
        if (!in_array($currency, $this->getConfig()->get('currencyList', []))) {
            throw new \Exception("Incorrect currency for field '{$config['name']}'");
        }

        // set values to input row
        $inputRow->{$config['name']} = (float)$value;
        $inputRow->{$config['name'] . 'Currency'} = $currency;
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        $restore->{$item['name'] . 'Currency'} = $entity->get($item['name'] . 'Currency');

        parent::prepareValue($restore, $entity, $item);
    }
}