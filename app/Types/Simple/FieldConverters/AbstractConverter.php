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
use Treo\Core\Container;
use Espo\Core\Utils\Config;
use Treo\Core\Utils\Metadata;

/**
 * Class AbstractConverter
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
