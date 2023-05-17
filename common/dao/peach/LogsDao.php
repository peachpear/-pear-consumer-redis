<?php

namespace common\dao\peach;

use common\models\peach\LogsModel;
use yii;

/**
 * Class Logs
 * @package common\dao\ttxm
 */
class LogsDao extends LogsModel
{
    /**
     * @param array $data
     * @return bool
     */
    public static function logPersistent($data = [])
    {
        $model = new parent();
        $model->level = $data['level'] ?? '';
        $model->log_id = $data['log_id'] ?? uniqid() . mt_rand(100000, 999999);
        $model->data = json_encode((array)$data);
        $model->created_at = $model->updated_at = date('Y-m-d H:i:s');

        return $model->save();
    }
}