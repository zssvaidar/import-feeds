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
use Treo\Core\Container;
use Espo\Core\Utils\Config;
use Treo\Core\Utils\Metadata;

/**
 * Class AbstractConverter
 */
abstract class AbstractConverter
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * AbstractConverter constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param \stdClass $inputRow
     * @param string    $entityType
     * @param array     $config
     * @param array     $row
     * @param string    $delimiter
     *
     * @return mixed
     */
    abstract public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter);

    /**
     * @param \stdClass $restore
     * @param Entity $entity
     * @param array $item
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        $field = $item['name'];

        $restore->{$field} = $entity->get($field);
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }
}
