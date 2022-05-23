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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Import\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\Listeners\AbstractListener;
use Espo\Core\EventManager\Event;

class AttachmentController extends AbstractListener
{
    public function beforeActionCreate(Event $event)
    {
        /** @var \stdClass $data */
        $data = $event->getArgument('data');

        if (
            !property_exists($data, 'relatedType')
            || $data->relatedType !== 'ImportFeed'
            || !property_exists($data, 'field')
            || !in_array($data->field, ['importFile', 'file'])
        ) {
            return;
        }

        $content = $this->parseInputFileContent($data->file);
        if (empty($content)) {
            throw new BadRequest($this->getLanguage()->translate('fileEmpty', 'exceptions', 'ImportFeed'));
        }

        if (property_exists($data, 'modelAttributes') && property_exists($data->modelAttributes, 'format')) {
            $method = "validate{$data->modelAttributes->format}File";
            $service = $this->getService('ImportFeed');
            if (method_exists($service, $method)) {
                $service->$method($data->type, $content);
            }
        }
    }

    protected function parseInputFileContent(string $fileContent): string
    {
        $arr = explode(',', $fileContent);
        $contents = '';
        if (count($arr) > 1) {
            $contents = $arr[1];
        }

        return base64_decode($contents);
    }
}
