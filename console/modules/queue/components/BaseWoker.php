<?php

namespace console\modules\queue\components;

use common\dao\peach\QueueErrorDao;
use yii\base\Component;
use yii;

/**
 * Class BaseWoker
 * @package console\modules\queue\components
 */
class BaseWoker extends Component
{
    /**
     * @var string
     */
    var $queue;

    /**
     * @var array
     */
    var $setting;

    /**
     * @var array
     */
    var $delay;

    /**
     * @param $queue
     * @param $name
     * @param $delay
     */
    public function run($queue, $name, $delay)
    {
        $this->queue = $queue;
        $this->setting = $name;
        $this->delay = $delay;

        static::execute();
    }

    /**
     * @return mixed
     */
    protected function execute()
    {
        $record = $this->queue->rpop($this->setting['name']);
        if (empty($record)) {
            usleep(100000);  // 0.1秒
            return self::execute();
        }

        /** 消费逻辑 **/
        $record = json_decode($record, true);

        $retry = 0;
        if (isset($record['retry']) && isset($record['queue']) && ($record['queue'] === $this->setting['name'])) {
            $retry = $record['retry'];
            $data = $record['data'];
        } else {
            $data = $record;
        }
        $retry++;

        // 超过尝试次数，数据持久化
        if ($retry > $this->setting['attempts']) {
            static::dataPersistent($this->setting['name'], $data);
            return self::execute();
        }

        // 数据处理
        try {
            static::doWorker($data);
        } catch (\Throwable $e) {
            // 推送延迟再处理
            static::pushDelayQueue($data, $retry);
        }

        return self::execute();
    }

    /**
     * @param $queue
     * @param $data
     * @return bool
     */
    protected function dataPersistent($queue, $data)
    {
        return QueueErrorDao::errorPersistent($queue, $data, QueueErrorDao::TYPE_ASYNCHRONOUS);
    }

    /**
     * @param $data
     * @param $retry
     * @return bool
     */
    protected function pushDelayQueue($data, $retry)
    {
        $data['tmp_rand'] = rand(1000000, 9999999);

        $this->queue->zadd($this->delay['name'], (time() + $this->setting['ttr']), json_encode([
                'queue' => $this->setting['name'],
                'data' => $data,
                'retry' => $retry])
        );

        return true;
    }
}