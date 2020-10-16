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
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Controllers\Base;

/**
 * Class ImportResult
 */
class ImportResult extends Base
{
    /**
     * @inheritDoc
     */
    public function actionListLinked($params, $data, $request)
    {
        if ($params['link'] == 'importResultLogs') {
            $where = $request->get('where');
            $where[] = [
                'type'      => 'in',
                'attribute' => 'type',
                'value'     => ['error']
            ];
            $request->setQuery('where', $where);
        }

        return parent::actionListLinked($params, $data, $request);
    }

    /**
     * @param array $params
     * @param array $data
     * @param object $request
     *
     * @throws BadRequest
     */
    public function actionRestore($params, $data, $request)
    {
        if (!$request->isPost() || !isset($data->id)) {
            throw new BadRequest();
        }

        return $this->getRecordService()->restore($data->id);
    }

    /**
     * @inheritDoc
     *
     * @throws NotFound
     */
    public function actionCreate($params, $data, $request)
    {
        throw new NotFound();
    }

    /**
     * @param array  $params
     * @param array  $data
     * @param object $request
     *
     * @throws NotFound
     */
    public function actionUpdate($params, $data, $request)
    {
        throw new NotFound();
    }

    /**
     * @inheritDoc
     *
     * @throws NotFound
     */
    public function actionMassUpdate($params, $data, $request)
    {
        throw new NotFound();
    }

    /**
     * @inheritDoc
     *
     * @throws NotFound
     */
    public function actionCreateLink($params, $data, $request)
    {
        throw new NotFound();
    }
}
