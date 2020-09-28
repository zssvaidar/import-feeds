<?php
declare(strict_types=1);

namespace Import\Types\Simple\FieldConverters;

use Espo\ORM\Entity;
use Treo\Core\Container;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Metadata;

/**
 * Class AbstractConverter
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractConverter
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * AbstractConverter constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param \stdClass $inputRow
     * @param string    $entityType
     * @param array     $config
     * @param array     $row
     * @param string    $delimiter
     *
     * @return mixed
     */
    abstract public function convert(\stdClass $inputRow, string $entityType, array $config, array $row, string $delimiter);

    /**
     * @param \stdClass $restore
     * @param Entity $entity
     * @param array $item
     */
    public function prepareValue(\stdClass $restore, Entity $entity, array $item)
    {
        $field = $item['name'];

        $restore->{$field} = $entity->get($field);
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->container->get('config');
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->container->get('metadata');
    }
}
