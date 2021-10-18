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

namespace Import\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Templates\Repositories\Base;
use Espo\Core\Utils\Json;
use Espo\ORM\Entity;

class ImportConfiguratorItem extends Base
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if (empty($importFeed = $entity->get('importFeed'))) {
            throw new BadRequest('ImportFeed is required for Configurator item.');
        }

        if ($entity->get('type') === 'Field') {
            $type = $this->getMetadata()->get(['entityDefs', $importFeed->getFeedField('entity'), 'fields', $entity->get('name'), 'type'], 'varchar');
        } elseif ($entity->get('type') === 'Attribute') {
            if (empty($attribute = $entity->get('attribute'))) {
                throw new BadRequest('No such Attribute.');
            }
            $type = $attribute->get('type');
        }

        if (in_array($type, ['link', 'asset']) && $entity->has('defaultId')) {
            $entity->set('default', empty($entity->get('defaultId')) ? null : $entity->get('defaultId'));
        }

        if ($type === 'linkMultiple' && $entity->has('defaultIds')) {
            $entity->set('default', empty($entity->get('defaultIds')) ? null : Json::encode($entity->get('defaultIds')));
        }

        if ($type === 'currency') {
            $old = !$entity->isNew() ? Json::decode($entity->getFetched('default'), true) : ['value' => 0, 'currency' => 'EUR'];
            $currencyData = [
                'value'    => $entity->has('default') && strpos((string)$entity->get('default'), '{') === false ? $entity->get('default') : $old['value'],
                'currency' => $entity->has('defaultCurrency') ? $entity->get('defaultCurrency') : $old['currency']
            ];

            $entity->set('default', Json::encode($currencyData));
        }

        if ($type === 'unit') {
            $old = !$entity->isNew() ? Json::decode($entity->getFetched('default'), true) : ['value' => 0, 'unit' => ''];
            $unitData = [
                'value' => $entity->has('default') && strpos((string)$entity->get('default'), '{') === false ? $entity->get('default') : $old['value'],
                'unit'  => $entity->has('defaultUnit') ? $entity->get('defaultUnit') : $old['unit']
            ];

            $entity->set('default', Json::encode($unitData));
        }

        if (in_array($type, ['array', 'multiEnum']) && $entity->isAttributeChanged('default')) {
            $entity->set('default', Json::encode($entity->get('default')));
        }

        if (empty($entity->get('column')) && empty($entity->get('default')) && $entity->get('default') !== false) {
            throw new BadRequest($this->getInjection('language')->translate('columnOrDefaultValueIsRequired', 'exceptions', 'ImportConfiguratorItem'));
        }

        parent::beforeSave($entity, $options);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('language');
    }
}
