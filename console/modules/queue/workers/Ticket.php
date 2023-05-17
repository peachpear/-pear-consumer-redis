<?php

namespace console\modules\queue\workers;

use common\service\TicketService;
use console\modules\queue\components\BaseWoker;
use Yii;

/**
 * Class Ticket
 * @package console\modules\queue\workers
 */
class Ticket extends BaseWoker
{

    /**
     * @param $data
     * e.g.队列中一条数据是 {"event":"ticket_callback",
     * "data":{"type":"total_of_ticket",
     * "infos":{"user_id":"1474"}}}
     */
    protected function doWorker($data)
    {
        $event = isset($data['event']) ? $data['event'] : '';

        switch ($event) {
            case 'ticket_callback':
                TicketService::callbackRequest($data['data']);
                break;
            default:
                break;
        }
    }
}