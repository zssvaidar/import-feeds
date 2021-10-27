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

use Espo\Core\Services\Base;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Core\Container;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\ORM\EntityManager;

class Varchar
{
    protected Container $container;
    protected array $services = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function convert(\stdClass $inputRow, array $config, array $row): void
    {
        if (isset($config['column'][0]) && isset($row[$config['column'][0]])) {
            $value = $row[$config['column'][0]];
            if ($value === $config['emptyValue'] || $value === '') {
                $value = empty($config['default']) ? '' : $config['default'];
            }
            if ($value === $config['nullValue']) {
                $value = null;
            }
        } else {
            $value = $config['default'];
        }

        $inputRow->{$config['name']} = $value;
    }

    public function prepareValue(\stdClass $restore, Entity $entity, array $item): void
    {
        $restore->{$item['name']} = $entity->get($item['name']);
    }

    public function prepareFindExistEntityWhere(array &$where, array $configuration, array $row): void
    {
        $value = $configuration['default'];

        if (isset($configuration['column'][0]) && isset($row[$configuration['column'][0]])) {
            $value = $row[$configuration['column'][0]];
        }

        $where[$configuration['name']] = $value;
    }

    public function prepareForSaveConfiguratorDefaultField(Entity $entity): void
    {
    }

    public function prepareForOutputConfiguratorDefaultField(Entity $entity): void
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

    protected function translate(string $label, string $category = 'labels', string $scope = 'Global'): string
    {
        return $this->container->get('language')->translate($label, $category, $scope);
    }

    protected function getService(string $name): Base
    {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->container->get('serviceFactory')->create($name);
        }

        return $this->services[$name];
    }
}
