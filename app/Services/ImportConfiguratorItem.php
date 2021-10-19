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
use Espo\Core\Utils\Json;
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

    protected function prepareDefaultField(string $type, Entity $entity): void
    {
        switch ($type) {
            case 'bool':
                $entity->set('default', !empty($entity->get('default')));
                break;
            case 'array':
            case 'multiEnum':
                $entity->set('default', !empty($entity->get('default')) ? Json::decode($entity->get('default'), true) : []);
                break;
            case 'currency':
                $currencyData = Json::decode($entity->get('default'), true);
                $entity->set('default', $currencyData['value']);
                $entity->set('defaultCurrency', $currencyData['currency']);
                break;
            case 'unit':
                $unitData = Json::decode($entity->get('default'), true);
                $entity->set('default', $unitData['value']);
                $entity->set('defaultUnit', $unitData['unit']);
                break;
            case 'asset':
                $entity->set('defaultId', null);
                $entity->set('defaultName', null);
                $entity->set('defaultPathsData', null);
                if (!empty($entity->get('default'))) {
                    $entity->set('defaultId', $entity->get('default'));
                    $relEntity = $this->getEntityManager()->getEntity('Attachment', $entity->get('defaultId'));
                    $entity->set('defaultName', empty($relEntity) ? $entity->get('defaultId') : $relEntity->get('name'));
                    $entity->set('defaultPathsData', $this->getEntityManager()->getRepository('Attachment')->getAttachmentPathsData($relEntity));
                }
                break;
            case 'link':
                $entity->set('defaultId', null);
                $entity->set('defaultName', null);
                if (!empty($entity->get('default'))) {
                    $relEntityName = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'links', $entity->get('name'), 'entity']);
                    if (!empty($relEntityName)) {
                        $entity->set('defaultId', $entity->get('default'));
                        $relEntity = $this->getEntityManager()->getEntity($relEntityName, $entity->get('defaultId'));
                        $entity->set('defaultName', empty($relEntity) ? $entity->get('defaultId') : $relEntity->get('name'));
                    }
                }
                break;
            case 'linkMultiple':
                $entity->set('defaultIds', null);
                $entity->set('defaultNames', null);
                if (!empty($entity->get('default'))) {
                    $relEntityName = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'links', $entity->get('name'), 'entity']);
                    if (!empty($relEntityName)) {
                        $entity->set('defaultIds', Json::decode($entity->get('default'), true));
                        $names = [];
                        foreach ($entity->get('defaultIds') as $id) {
                            $relEntity = $this->getEntityManager()->getEntity($relEntityName, $id);
                            $names[$id] = empty($relEntity) ? $id : $relEntity->get('name');
                        }
                        $entity->set('defaultNames', $names);
                    }
                }
                break;
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
