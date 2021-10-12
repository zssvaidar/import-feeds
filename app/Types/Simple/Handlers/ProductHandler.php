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

namespace Import\Types\Simple\Handlers;

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\Services\Record;
use Treo\Core\Exceptions\NotModified;

/**
 * Class ProductHandler
 */
class ProductHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $images = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var bool
     */
    protected $saved = false;

    /**
     * @param array $fileData
     * @param array $data
     *
     * @return bool
     */
    public function run(array $fileData, array $data): bool
    {
        // prepare entity type
        $entityType = (string)$data['data']['entity'];

        // prepare import result id
        $importResultId = (string)$data['data']['importResultId'];

        // prepare field value delimiter
        $delimiter = $data['data']['delimiter'];

        // create service
        $service = $this->getServiceFactory()->create($entityType);

        // prepare file row
        $fileRow = (int)$data['offset'];

        foreach ($fileData as $row) {
            $fileRow++;

            $entity = null;
            if ($data['action'] == 'update') {
                $entity = $this->findExistEntity('Product', $data['data'], $row);
                if (empty($entity)) {
                    continue 1;
                }
            } elseif ($data['action'] == 'create_update') {
                $entity = $this->findExistEntity('Product', $data['data'], $row);
            }

            $id = empty($entity) ? null : $entity->get('id');

            // prepare row
            $input = new \stdClass();
            $restore = new \stdClass();

            try {
                // begin transaction
                $this->getEntityManager()->getPDO()->beginTransaction();

                $additionalFields = [];

                foreach ($data['data']['configuration'] as $item) {
                    if ($item['name'] == 'id') {
                        continue;
                    }

                    if (isset($item['attributeId']) || $item['name'] == 'productCategories') {
                        $additionalFields[] = [
                            'item' => $item,
                            'row'  => $row
                        ];

                        continue;
                    } else {
                        $this->convertItem($input, $entityType, $item, $row, $delimiter);
                    }

                    if (!empty($entity)) {
                        $this->prepareValue($restore, $entity, $item);
                    }
                }

                if (empty($id)) {
                    $entity = $service->createEntity($input);

                    $this->saveRestoreRow('created', $entityType, $entity->get('id'));

                    $this->saved = true;
                } else {
                    $entity = $this->updateEntity($service, (string)$id, $input);

                    if ($entity->isSaved()) {
                        $this->saveRestoreRow('updated', $entityType, [$id => $restore]);
                        $this->saved = true;
                    }
                }

                // prepare product attributes
                $this->attributes = $entity->get('productAttributeValues');

                foreach ($additionalFields as $value) {
                    if (isset($value['item']['attributeId'])) {
                        // import attributes
                        $this->importAttribute($entity, $value, $delimiter);
                    }
                }

                if (!is_null($entity) && $this->saved) {
                    // prepare action
                    $action = empty($id) ? 'create' : 'update';

                    // push log
                    $this->log($entityType, $importResultId, $action, (string)$fileRow, (string)$entity->get('id'));
                }

                $this->saved = false;

                $this->getEntityManager()->getPDO()->commit();

                if ($action === 'create') {
                    $this->afterCreateAction($entity);
                }
            } catch (\Throwable $e) {
                // roll back transaction
                $this->getEntityManager()->getPDO()->rollBack();

                // push log
                $this->log($entityType, $importResultId, 'error', (string)$fileRow, $e->getMessage());
            }
        }

        return true;
    }

    /**
     * @param Record    $service
     * @param string    $id
     * @param \stdClass $data
     */
    protected function updateEntity(Record $service, string $id, \stdClass $data): ?Entity
    {
        try {
            $result = $service->updateEntity($id, $data);
        } catch (NotModified $e) {
            $result = $service->readEntity($id);
        }

        return $result;
    }

    /**
     * @param Entity $product
     * @param array  $data
     * @param string $delimiter
     */
    protected function importAttribute(Entity $product, array $data, string $delimiter)
    {
        $entityType = 'ProductAttributeValue';
        $service = $this->getServiceFactory()->create($entityType);

        $inputRow = new \stdClass();
        $restoreRow = new \stdClass();

        $conf = $data['item'];
        $conf['name'] = 'value';
        // check for multiLang
        if (isset($conf['locale']) && !is_null($conf['locale'])) {
            if ($this->getConfig()->get('isMultilangActive')) {
                $conf['name'] .= Util::toCamelCase(strtolower($conf['locale']), '_', true);
            }
        }
        $row = $data['row'];

        foreach ($this->attributes as $item) {
            if ($item->get('attributeId') == $conf['attributeId'] && $item->get('scope') == $conf['scope']) {
                if ($conf['scope'] == 'Global'
                    || ($conf['scope'] == 'Channel' && $conf['channelId'] == $item->get('channelId'))) {
                    $inputRow->id = $item->get('id');
                    $this->prepareValue($restoreRow, $item, $conf);
                }
            }
        }

        // prepare attribute
        if (!isset($this->attributes[$conf['attributeId']])) {
            $attribute = $this->getEntityManager()->getEntity('Attribute', $conf['attributeId']);
            $this->attributes[$conf['attributeId']] = $attribute;
        } else {
            $attribute = $this->attributes[$conf['attributeId']];
        }
        $conf['attribute'] = $attribute;

        // convert attribute value
        $this->convertItem($inputRow, $entityType, $conf, $row, $delimiter);

        if (!isset($inputRow->id)) {
            $inputRow->productId = $product->get('id');
            $inputRow->attributeId = $conf['attributeId'];
            $inputRow->scope = $conf['scope'];

            if ($conf['scope'] == 'Channel') {
                $inputRow->channelId = $conf['channelId'];
                $inputRow->channelName = $conf['channelName'];
            }

            $entity = $service->createEntity($inputRow);
            $this->attributes[] = $entity;

            $this->saveRestoreRow('created', $entityType, $entity->get('id'));

            $this->saved = true;
        } else {
            $id = $inputRow->id;
            unset($inputRow->id);

            $entity = $this->updateEntity($service, $id, $inputRow);

            if ($entity->isSaved()) {
                $this->saveRestoreRow('updated', $entityType, [$id => $restoreRow]);
                $this->saved = true;
            }
        }
    }

    protected function getType(string $entityType, array $item): ?string
    {
        if (isset($item['attributeId']) && isset($item['type'])) {
            return $item['type'];
        }

        return parent::getType($entityType, $item);
    }

    protected function afterCreateAction(Entity $entity): void
    {
        if (!empty($entity->get('data'))) {
            $productData = Json::decode(Json::encode($entity->get('data')), true);
            if (!empty($productData['productAssets'])) {
                foreach ($productData['productAssets'] as $row) {
                    if (empty($row['channelId'])) {
                        $channelId = '';
                    } else {
                        $channel = $this->getEntityManager()->getRepository('Channel')->where(['code' => $row['channelId']])->findOne();
                        if (!empty($channel)) {
                            $channelId = $channel->get('id');
                        }
                    }

                    $sql = "UPDATE product_asset SET channel='$channelId' WHERE asset_id='{$row['assetId']}' AND product_id='{$entity->get('id')}' AND deleted=0";

                    try {
                        $this->getEntityManager()->nativeQuery($sql);
                    } catch (\Throwable $e) {
                        $GLOBALS['log']->error('Updating of Product Asset relation failed. Message: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}