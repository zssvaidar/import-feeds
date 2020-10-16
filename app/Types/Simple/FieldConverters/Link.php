<?php
/*
 * This file is part of premium software, which is NOT free.
 * Copyright (c) AtroCore UG (haftungsbeschränkt).
 *
 * This Software is the property of AtroCore UG (haftungsbeschränkt) and is
 * protected by copyright law - it is NOT Freeware and can be used only in one
 * project under a proprietary license, which is delivered along with this program.
 * If not, see <https://atropim.com/eula> or <https://atrodam.com/eula>.
 *
 * This Software is distributed as is, with LIMITED WARRANTY AND LIABILITY.
 * Any unauthorised use of this Software without a valid license is
 * a violation of the License Agreement.
 *
 * According to the terms of the license you shall not resell, sublicense,
 * rent, lease, distribute or otherwise transfer rights or usage of this
 * Software or its derivatives. You may modify the code of this Software
 * for your own needs, if source code is provided.
 */

declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

use Espo\ORM\Entity;

/**
 * Class Link
 */
class Link extends AbstractConverter
{
    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        // prepare default entity id
        $value = $config['default'];

        // prepare default entity name
        $name = isset($config['defaultName']) ? $config['defaultName'] : null;

        if (!is_null($config['column']) && !empty($row[$config['column']])) {
            // get entity name
            $entityName = $this
                ->container
                ->get('metadata')
                ->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);

            $entity = $this
                ->container
                ->get('entityManager')
                ->getRepository($entityName)
                ->select(['id', 'name'])
                ->where([$config['field'] => $row[$config['column']]])
                ->findOne();

            if (!empty($entity)) {
                $value = $entity->get('id');
                $name = $entity->get('name');
            } else {
                throw new \Exception("Not found any entities for field '{$config['name']}'");
            }
        }

        $inputRow->{$config['name'] . 'Id'} = $value;
        $inputRow->{$config['name'] . 'Name'} = $name;
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        $value = null;

        if (!empty($foreign = $entity->get($item['name']))) {
            $value = $foreign->get('id');
        }

        $restore->{$item['name'] . 'Id'} = $value;
    }
}
