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
 * Class LinkMultiple
 */
class LinkMultiple extends AbstractConverter
{
    /**
     * @inheritDoc
     */
    public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter)
    {
        // prepare ids
        $ids = explode(',', $config['default']);

        // prepare names
        $names = isset($config['defaultNames']) ? array_values($config['defaultNames']) : [];

        if (!is_null($config['column']) && !empty($row[$config['column']])) {
            $ids = explode($delimiter, $row[$config['column']]);

            // get entity name
            $entityName = $this
                ->container
                ->get('metadata')
                ->get(['entityDefs', $entityType, 'links', $config['name'], 'entity']);

            if (!empty($entityName)) {
                // find entity
                $entities = $this
                    ->container
                    ->get('entityManager')
                    ->getRepository($entityName)
                    ->select(['id', 'name'])
                    ->where([$config['field'] => $ids])
                    ->find()
                    ->toArray();

                if (count($entities) > 0) {
                    $ids = array_column($entities, 'id');
                    $names = array_column($entities, 'name');
                }
            }
        }

        $inputRow->{$config['name'] . 'Ids'} = (array)$ids;
        $inputRow->{$config['name'] . 'Names'} = $names;
    }

    /**
     * @inheritDoc
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        $ids = null;
        $names = null;
        $foreigners = $entity->get($item['name'])->toArray();

        if (count($foreigners) > 0) {
            $ids = array_column($foreigners, 'id');
            $names = array_column($foreigners, 'id');
        }

        $restore->{$item['name'] . 'Ids'} = $ids;
        $restore->{$item['name'] . 'Names'} = $names;
    }
}