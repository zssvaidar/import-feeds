<?php

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

/**
 * Class JsonArray
 *
 * @author r.zablodskiy@treolabs.com
 */
class JsonArray extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        $value = null;

        $value
            = (isset($row[$config['column']]) && !empty($row[$config['column']])) ? $row[$config['column']] : $config['default'];

        if (is_string($value)) {
            $value = explode($delimiter, $value);
        }

        $inputRow->{$config['name']} = Json::encode($value);
    }
}
