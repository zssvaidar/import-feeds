<?php
declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

use Espo\ORM\Entity;

/**
 * Class Link
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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
