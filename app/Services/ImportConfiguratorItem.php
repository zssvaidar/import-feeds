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

namespace Import\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;

class ImportConfiguratorItem extends Base
{
    protected $mandatorySelectAttributeList = ['importFeedId', 'importBy', 'createIfNotExist', 'default', 'type', 'attributeId', 'scope', 'locale'];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if (empty($importFeed = $entity->get('importFeed'))) {
            return;
        }

        $entity->set('entity', $importFeed->getFeedField('entity'));

        if ($entity->get('type') === 'Attribute') {
            if (empty($attribute = $this->getEntityManager()->getEntity('Attribute', $entity->get('attributeId')))) {
                throw new BadRequest('No such Attribute.');
            }
            $entity->set('name', $attribute->get('name'));
            $entity->set('attributeType', $attribute->get('type'));
            $entity->set('attributeTypeValue', $attribute->get('typeValue'));
            $entity->set('attributeIsMultilang', $attribute->get('isMultilang'));
            $fieldType = $attribute->get('type');
        } else {
            $fieldType = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'fields', $entity->get('name'), 'type'], 'varchar');
        }

        $this->prepareDefaultField($fieldType, $entity);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('container');
    }

    protected function prepareDefaultField(string $type, Entity $entity): void
    {
        $converter = $this->getMetadata()->get(['import', 'simple', 'fields', $type, 'converter']);
        if (!empty($converter)) {
            (new $converter($this->getInjection('container')))->prepareForOutputConfiguratorDefaultField($entity);
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
