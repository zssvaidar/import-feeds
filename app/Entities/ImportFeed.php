<?php
declare(strict_types=1);

namespace Import\Entities;

/**
 * Class ImportFeed
 *
 * @author r.ratsun@treolabs.com
 */
class ImportFeed extends \Espo\Core\Templates\Entities\Base
{
    /**
     * @var string
     */
    protected $entityType = "ImportFeed";

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return (!empty($this)) ? (string)$this->get('fileFieldDelimiter') : ";";
    }

    /**
     * @return string
     */
    public function getEnclosure(): string
    {
        return (!empty($this) && $this->get('fileTextQualifier') == 'singleQuote') ? "'" : '"';
    }

    /**
     * @return bool
     */
    public function isFileHeaderRow(): bool
    {
        return (!empty($this) && !empty($this->get('isFileHeaderRow'))) ? true : false;
    }
}
