<?php

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

/**
 * Class FloatValue
 *
 * @author r.zablodskiy@treolabs.com
 */
class FloatValue extends AbstractConverter
{
    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        $value = (!empty($config['column']) && $row[$config['column']] != '') ? $row[$config['column']] : $config['default'];

        if (!is_null($value) && filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            throw new \Exception("Incorrect value for field '{$config['name']}'");
        }

        $inputRow->{$config['name']} = $value;
    }
}