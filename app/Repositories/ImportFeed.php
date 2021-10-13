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

class ImportFeed extends Base
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        $this->setFeedFieldsToDataJson($entity);

        if ($entity->get('type') === 'simple') {
            $data = $entity->getFeedFields();

            // validation
            if ($data['delimiter'] === $data['fileFieldDelimiter']) {
                throw new BadRequest($this->getInjection('language')->translate('delimitersMustBeDifferent', 'messages', 'ImportFeed'));
            }
        }

        parent::beforeSave($entity, $options);
    }

    protected function setFeedFieldsToDataJson(Entity $entity): void
    {
        $data = !empty($data = $entity->get('data')) ? Json::decode(Json::encode($data), true) : [];

        foreach ($this->getMetadata()->get(['entityDefs', 'ImportFeed', 'fields'], []) as $field => $row) {
            if (empty($row['notStorable'])) {
                continue 1;
            }

            if ($entity->has($field)) {
                $data['feedFields'][$field] = $entity->get($field);

                switch ($row['type']) {
                    case 'int':
                        $data['feedFields'][$field] = (int)$data['feedFields'][$field];
                        break;
                    case 'bool':
                        $data['feedFields'][$field] = !empty($data['feedFields'][$field]);
                        break;
                }
            }
        }

        $entity->set('data', $data);
    }

    protected function init()
    {
        parent::init();

        $this->addDependency('language');
    }
}
