<?php
namespace console\modules\queue\workers;

use common\service\MailService;
use console\modules\queue\components\BaseWoker;
use Yii;

/**
 * Class Mail
 * @package console\modules\queue\workers
 */
class Mail extends BaseWoker
{
    /**
     * @param $data
     * 数组：【send_to、cc_to、text、title、file】
     * @return bool
     * @throws \Exception
     */
    protected function doWorker($data)
    {
        return MailService::sendMailOfQueue($data);
    }
}