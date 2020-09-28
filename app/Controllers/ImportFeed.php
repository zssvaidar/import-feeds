<?php
declare(strict_types=1);

namespace Import\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

/**
 * ImportFeed controller
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
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
