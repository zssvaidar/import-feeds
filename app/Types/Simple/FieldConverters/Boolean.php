<?php

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

/**
 * Class Boolean
 *
 * @author r.zablodskiy@treolabs.com
 */
class Boolean extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        $result = (isset($config['column']) && ($row[$config['column']]) != '') ? $row[$config['column']] : $config['default'];

        if (is_null(filter_var($result, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
            throw new \Exception("Incorrect value for field '{$config['name']}'");
        }

        $inputRow->{$config['name']} = (bool)$result;
    }
}
