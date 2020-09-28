<?php

declare(strict_types=1);

namespace Import\Services;

use Treo\Entities\Attachment;

/**
 * Class CsvFileParser
 *
 * @author r.ratsun@treolabs.com
 */
class CsvFileParser extends \Treo\Services\AbstractService
{

    /**
     * @param Attachment $attachment
     * @param string     $delimiter
     * @param string     $enclosure
     * @param bool       $isFileHeaderRow
     *
     * @return array
     */
    public function getFileColumns(
        Attachment $attachment,
        string $delimiter = ";",
        string $enclosure = '"',
        bool $isFileHeaderRow = true
    ): array {
        // prepare result
        $result = [];

        // get data
        $data = $this->getFileData($attachment, $delimiter, $enclosure, 0, 2);

        if (isset($data[0])) {
            if ($isFileHeaderRow && isset($data[1])) {
                foreach ($data[0] as $k => $value) {
                    $result[] = $this->prepareFileColumn($k, $value, $data[1][$k]);
                }
            } else {
                foreach ($data[0] as $k => $value) {
                    $result[] = $this->prepareFileColumn($k, 'Column ' . $k, $value);
                }
            }
        }

        return $result;
    }

    /**
     * @param Attachment $attachment
     * @param string     $delimiter
     * @param string     $enclosure
     * @param int        $offset
     * @param int        $limit
     *
     * @return array
     */
    public function getFileData(
        Attachment $attachment,
        string $delimiter = ";",
        string $enclosure = '"',
        int $offset = 0,
        int $limit = null
    ): array {
        // prepare path
        $path = $this->getLocalFilePath($attachment);

        return $this->getParsedFileData($path, $delimiter, $enclosure, $offset, $limit);
    }

    /**
     * @param Attachment $attachment
     * @param string     $delimiter
     * @param string     $enclosure
     *
     * @return int
     */
    public function getCountRows(Attachment $attachment, string $delimiter = ";", string $enclosure = '"'): int
    {
        return $this->getFileRowsCount($this->getLocalFilePath($attachment), $delimiter, $enclosure);
    }

    /**
     * @param Attachment $attachment
     *
     * @return string
     */
    protected function getLocalFilePath(Attachment $attachment): string
    {
        $path = $this
            ->getContainer()
            ->get('fileStorageManager')
            ->getLocalFilePath($attachment);

        return (empty($path)) ? '' : (string)$path;
    }

    /**
     * @param string $path
     * @param string $delimiter
     * @param string $enclosure
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     */
    protected function getParsedFileData(
        string $path,
        string $delimiter = ";",
        string $enclosure = '"',
        int $offset = 0,
        int $limit = null
    ): array {
        // prepare result
        $result = [];

        if (file_exists($path) && ($handle = fopen($path, "r")) !== false) {
            $row = 0;
            $count = 0;
            while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false
                && (is_null($limit) || $count < $limit)) {
                if ($row >= $offset) {
                    // push
                    $result[] = $data;

                    // increase
                    $count++;
                }

                // increase
                $row++;
            }
            fclose($handle);
        }

        return $result;
    }

    /**
     * @param string $path
     * @param string $delimiter
     * @param string $enclosure
     *
     * @return int
     */
    protected function getFileRowsCount(string $path, string $delimiter = ";", string $enclosure = '"'): int
    {
        // prepare result
        $result = 0;

        if (file_exists($path) && ($handle = fopen($path, "r")) !== false) {
            while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
                $result++;
            }
            fclose($handle);
        }

        return $result;
    }

    /**
     * @param int $column
     * @param string $name
     * @param $value
     *
     * @return array
     */
    protected function prepareFileColumn(int $column, string $name, $value): array
    {
        $result = [
            'column' => $column,
            'name' => $name,
            'firstValue' => $value
        ];

        return $result;
    }
}
