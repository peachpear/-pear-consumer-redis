<?php

namespace console\modules\queue\controllers;

use common\dao\peach\QueueErrorDao;
use console\components\BaseController;
use Yii;

/**
 * Class DelayController
 * @package console\modules\queue\controllers
 */
class DelayController extends BaseController
{
    /**
     * queue
     * @var string
     */
    private $queue;

    /**
     * consumer
     * @var string
     */
    private $consumer;

    /**
     * worker对应queue绑定配置
     * @var array
     */
    private $bindSetting = [];

    /**
     * @var string
     */
    private $queue_name;

    /**
     * consumer初始化
     * @param $consumer
     */
    private function initConsumer($consumer)
    {
        $this->queue = Yii::$app->redis;

        $this->consumer = ucfirst($consumer);

        $this->bindSetting = Yii::$app->params['queue']['D' . $this->consumer];

        $this->queue_name = $this->bindSetting['name'];
    }

    /**
     * 启动consumer-worker
     * @param $consumer
     */
    public function actionStart($consumer)
    {
        $this->initConsumer($consumer);

        $this->msgQueue();
    }

    /**
     * 连接MQ并消费
     */
    private function msgQueue()
    {
        $list = $this->queue->zrangebyscore($this->queue_name, 0, time());
        if (empty($list)) {
            usleep(100000);  // 0.1秒
            return self::msgQueue();
        }

        foreach ($list as $one_json) {
            $one_arr = [];
            try {
                // 推送队列
                $one_arr = json_decode($one_json, true);

                $this->queue->lpush($one_arr['queue'], $one_json);
            } catch (\Throwable $e) {
                // 异常持久化
                QueueErrorDao::errorPersistent($this->queue_name, $one_arr, QueueErrorDao::TYPE_DELAY);
            }

            // 移除成员
            $this->queue->zrem($this->queue_name, $one_json);
        }

        return self::msgQueue();
    }
}