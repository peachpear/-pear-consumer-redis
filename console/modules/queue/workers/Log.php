<?php
namespace console\modules\queue\workers;

use common\dao\peach\LogsDao;
use console\modules\queue\components\BaseWoker;
use yii;

/**
 * Class Log
 * @package console\modules\queue\workers
 */
class Log extends BaseWoker
{
    /**
     * @param $data
     * @return bool
     */
    protected function doWorker($data)
    {
        return LogsDao::logPersistent($data);
    }
}