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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Import\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;
use Import\FieldConverters\Varchar;

class ImportConfiguratorItem extends Base
{
    protected $mandatorySelectAttributeList = ['importFeedId', 'importBy', 'createIfNotExist', 'replaceRelation', 'default', 'type', 'attributeId', 'scope', 'locale', 'sortOrder', 'foreignColumn', 'foreignImportBy'];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if (empty($importFeed = $entity->get('importFeed'))) {
            return;
        }

        $entity->set('entity', $importFeed->getFeedField('entity'));
        $entity->set('allColumns', $importFeed->getFeedField('allColumns'));
        $entity->set('unusedColumns', $importFeed->getUnusedColumns());

        if ($entity->get('type') === 'Attribute') {
            if (empty($attribute = $this->getEntityManager()->getEntity('Attribute', $entity->get('attributeId')))) {
                throw new BadRequest('No such Attribute.');
            }
            $entity->set('name', $attribute->get('name'));
            $entity->set('attributeCode', $attribute->get('code'));
            $entity->set('attributeType', $attribute->get('type'));
            $entity->set('attributeTypeValue', $attribute->get('typeValue'));
            $entity->set('attributeIsMultilang', $attribute->get('isMultilang'));
            $fieldType = $attribute->get('type');
        } else {
            $fieldType = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'fields', $entity->get('name'), 'type'], 'varchar');
        }

        $this->prepareDefaultField($fieldType, $entity);
    }

    public function getFieldConverter($type)
    {
        $class = $this->getMetadata()->get(['import', 'configurator', 'fields', $type, 'converter'], Varchar::class);

        return new $class($this->getInjection('container'));
    }

    public function updateEntity($id, $data)
    {
        if (property_exists($data, '_sortedIds')) {
            foreach ($data->_sortedIds as $k => $id) {
                if (!empty($item = $this->getRepository()->get($id))) {
                    $item->set('sortOrder', $k * 10);
                    $this->getEntityManager()->saveEntity($item);
                }
            }
            return $this->readEntity($id);
        }

        return parent::updateEntity($id, $data);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('container');
    }

    protected function prepareDefaultField(string $type, Entity $entity): void
    {
        if (!empty($converter = $this->getFieldConverter($type))) {
            $converter->prepareForOutputConfiguratorDefaultField($entity);
        }
    }

    protected function getFieldsThatConflict(Entity $entity, \stdClass $data): array
    {
        return [];
    }

    protected function isEntityUpdated(Entity $entity, \stdClass $data): bool
    {
        return true;
    }
}
