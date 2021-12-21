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

namespace Import\Services;

use Espo\Entities\Attachment;

/**
 * Class CsvFileParser
 */
class CsvFileParser extends \Espo\Core\Templates\Services\HasContainer
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
                    $result[] = $value;
                }
            } else {
                foreach ($data[0] as $k => $value) {
                    $result[] = (string)($k + 1);
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
            while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false && (is_null($limit) || $count < $limit)) {
                if ($row >= $offset) {
                    foreach ($data as &$v) {
                        preg_match_all('/' . $enclosure . '(.*)' . $enclosure . '$/', (string)$v, $matches);
                        if (isset($matches[1][0])) {
                            $v = $matches[1][0];
                        }
                    }
                    unset($v);

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
}
