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

namespace Import\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

/**
 * ImportFeed controller
 */
class ImportFeed extends \Espo\Core\Templates\Controllers\Base
{
    /**
     * @ApiDescription(description="Get file columns")*
     * @ApiMethod(type="GET")
     * @ApiRoute(name="/ImportFeed/{attachmentId}/fileColumns")
     * @ApiParams(name="attachmentId", type="string", is_required=1, description="Attachment id")
     * @ApiReturn(sample="[{'column': 'integer','name': 'string','firstValue': 'string'}]")
     *
     * @param array  $params
     * @param array  $data
     * @param object $request
     *
     * @return array
     * @throws BadRequest
     * @throws Forbidden
     */
    public function actionGetFileColumns($params, $data, $request): array
    {
        // checking request
        if (!$request->isGet() || empty($params['attachmentId'])) {
            throw new BadRequest();
        }

        // checking rules
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        return $this->getRecordService()->getFileColumns($params['attachmentId'], $request);
    }

    /**
     * @ApiDescription(description="Run import")*
     * @ApiMethod(type="POST")
     * @ApiRoute(name="/ImportFeed/action/RunImport")
     * @ApiBody(sample="{'importFeedId': '5bf7ccef1f2bac8b6','attachmentId':'1bf7ccef1f2bac8b6'}")
     * @ApiReturn(sample="'bool'")
     *
     * @param array  $params
     * @param array  $data
     * @param object $request
     *
     * @return bool
     * @throws BadRequest
     * @throws Forbidden
     */
    public function actionRunImport($params, $data, $request): bool
    {
        // checking request
        if (!$request->isPost() || empty($data->importFeedId) || empty($data->attachmentId)) {
            throw new BadRequest();
        }

        // checking rules
        if (!$this->getAcl()->check($this->name, 'read')) {
            throw new Forbidden();
        }

        return $this->getRecordService()->runImport($data->importFeedId, $data->attachmentId);
    }
}
