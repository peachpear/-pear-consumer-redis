<?php

namespace common\dao\peach;

use common\models\peach\QueueErrorModel;
use yii;

/**
 * Class QueueErrorDao
 * @package common\dao\ttxm
 */
class QueueErrorDao extends QueueErrorModel
{
    /**
     * @param $queue
     * @param array $data
     * @param int $type
     * @return bool
     */
    public static function errorPersistent($queue, $data = [], $type = parent::TYPE_ASYNCHRONOUS)
    {
        $model = new parent();
        $model->type = $type;
        $model->queue = (string)$queue;
        $model->data = json_encode((array)$data);
        $model->created_at = $model->updated_at = date('Y-m-d H:i:s');

        return $model->save();
    }
}