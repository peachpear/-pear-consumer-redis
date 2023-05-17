<?php

namespace common\models\peach;

use common\lib\LActiveRecord;
use yii;

/**
 * Class LogsModel
 * @package common\models\peach
 */
class LogsModel extends LActiveRecord
{
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
        return 'logs';
    }
}