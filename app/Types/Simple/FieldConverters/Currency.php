<?php

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

use Espo\ORM\Entity;

/**
 * Class Currency
 *
 * @author r.zablodskiy@treolabs.com
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