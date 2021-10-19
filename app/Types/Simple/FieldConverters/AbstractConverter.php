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

use Espo\Core\Services\Base;
use Espo\ORM\Entity;
use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;

abstract class AbstractConverter
{
    protected Container $container;
    protected array $services = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter): void
    {
    }

    public function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        $field = $item['name'];

        $restore->{$field} = $entity->get($field);
    }

    public function prepareConfiguratorDefaultField(string $type, Entity $entity): void
    {
    }

    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    protected function getService(string $name): Base
    {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->container->get('serviceFactory')->create($name);
        }

        return $this->services[$name];
    }
}
