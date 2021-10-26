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

/**
 * Class FloatValue
 */
class FloatValue extends Varchar
{
    public static function prepareFloatValue(string $value): float
    {
        return (float)str_replace(',', '.', preg_replace("/[^0-9]\.,/", "", $value));
    }

    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        $value = (!empty($config['column'][0]) && $row[$config['column'][0]] != '') ? $row[$config['column'][0]] : $config['default'];

        $inputRow->{$config['name']} = self::prepareFloatValue((string)$value);
    }
}