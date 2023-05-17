<?php

namespace common\models\peach;

use common\lib\LActiveRecord;
use yii;

/**
 * Class QueueErrorModel
 * @package common\models\ttxm
 */
class QueueErrorModel extends LActiveRecord
{
    const TYPE_ASYNCHRONOUS = 1;  // 异步队列
    const TYPE_DELAY = 2;  // 延迟队列

    /**
     * @return mixed|yii\db\Connection
     */
    public static function getDb()
    {
        return Yii::$app->peachDB;
    }

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'queue_error';
    }
}