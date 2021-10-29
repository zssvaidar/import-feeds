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
use Espo\Core\Utils\Language;
use Espo\ORM\Entity;

class ImportFeed extends Base
{
    public function getLanguage(): Language
    {
        return $this->getInjection('language');
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        $this->setFeedFieldsToDataJson($entity);

        $removeConfigurator = false;

        if ($entity->get('type') === 'simple') {
            $this->validateSimpleType($entity);
            $removeConfigurator = $entity->has('entity') && !$entity->isNew() && $entity->getFeedField('entity') !== $entity->get('entity');
            if (!$removeConfigurator && $entity->isAttributeChanged('fileId')) {
                $removeConfigurator = true;
            }
        }

        parent::beforeSave($entity, $options);

        if ($removeConfigurator) {
            $this
                ->getEntityManager()
                ->getRepository('ImportConfiguratorItem')
                ->where(['importFeedId' => $entity->get('id')])
                ->removeCollection();
        }
    }

    protected function validateSimpleType(Entity $entity): void
    {
        if ($entity->getFeedField('delimiter') === $entity->getFeedField('fileFieldDelimiter')) {
            throw new BadRequest($this->getLanguage()->translate('delimitersMustBeDifferent', 'messages', 'ImportFeed'));
        }

        if ($entity->getFeedField('fileFieldDelimiter') === '|' || $entity->getFeedField('delimiter') === '|') {
            throw new BadRequest($this->getLanguage()->translate("pipelineIsNotAllowed", "exceptions", "ImportFeed"));
        }

        if ($entity->getFeedField('emptyValue') === $entity->getFeedField('nullValue')) {
            throw new BadRequest($this->getLanguage()->translate("nullNoneSame", "exceptions", "ImportFeed"));
        }
    }

    protected function setFeedFieldsToDataJson(Entity $entity): void
    {
        $data = !empty($data = $entity->get('data')) ? Json::decode(Json::encode($data), true) : [];

        foreach ($this->getMetadata()->get(['entityDefs', 'ImportFeed', 'fields'], []) as $field => $row) {
            if (empty($row['notStorable']) || empty($row['dataField'])) {
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
