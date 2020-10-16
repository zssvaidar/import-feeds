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
