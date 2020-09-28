<?php
declare(strict_types=1);

namespace Import\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Controllers\Base;

/**
 * Class ImportResult
 *
 * @author r.ratsun@treolabs.com
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
