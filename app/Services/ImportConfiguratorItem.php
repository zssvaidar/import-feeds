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

use Espo\Core\Templates\Services\Base;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

class ImportConfiguratorItem extends Base
{
    protected $mandatorySelectAttributeList = ['importFeedId', 'importBy', 'createIfNotExist', 'default', 'type', 'attributeId', 'scope'];

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if (!empty($importFeed = $entity->get('importFeed'))) {
            $entity->set('entity', $importFeed->getFeedField('entity'));

            if ($entity->get('type') === 'Attribute') {
                if (!empty($attribute = $this->getEntityManager()->getEntity('Attribute', $entity->get('attributeId')))) {
                    $entity->set('name', $attribute->get('name'));
                } else {
                    $entity->set('name', '-');
                }
            }

            $fieldType = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'fields', $entity->get('name'), 'type'], 'varchar');

            if ($fieldType === 'bool') {
                $entity->set('default', !empty($entity->get('default')));
            }

            if ($fieldType === 'currency') {
                $currencyData = Json::decode($entity->get('default'), true);
                $entity->set('default', $currencyData['value']);
                $entity->set('defaultCurrency', $currencyData['currency']);
            }

            if ($fieldType === 'unit') {
                $unitData = Json::decode($entity->get('default'), true);
                $entity->set('default', $unitData['value']);
                $entity->set('defaultUnit', $unitData['unit']);
            }

            if (!empty($entity->get('default'))) {
                // prepare links
                $linkData = $this->getMetadata()->get(['entityDefs', $entity->get('entity'), 'links', $entity->get('name')]);
                if (!empty($linkData['type'])) {
                    if ($linkData['type'] === 'belongsTo') {
                        $entity->set('defaultId', $entity->get('default'));
                        $relEntity = $this->getEntityManager()->getEntity($linkData['entity'], $entity->get('defaultId'));
                        $entity->set('defaultName', empty($relEntity) ? $entity->get('defaultId') : $relEntity->get('name'));
                    }

                    if ($linkData['type'] === 'hasMany') {
                        $entity->set('defaultIds', Json::decode($entity->get('default'), true));
                        $names = [];
                        foreach ($entity->get('defaultIds') as $id) {
                            $relEntity = $this->getEntityManager()->getEntity($linkData['entity'], $id);
                            $names[$id] = empty($relEntity) ? $id : $relEntity->get('name');
                        }
                        $entity->set('defaultNames', $names);
                    }
                }
            }
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
