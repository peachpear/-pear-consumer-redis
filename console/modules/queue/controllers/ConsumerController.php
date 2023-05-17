<?php

namespace console\modules\queue\controllers;

use console\components\BaseController;
use yii;

/**
 * Class ConsumerController
 * @package console\modules\queue\controllers
 */
class ConsumerController extends BaseController
{
    /**
     * 项目根目录
     * @var string
     */
    private $root;

    /**
     * worker pidfile
     * @var string
     */
    private $pidfile;

    /**
     * worker进程pid
     * @var array
     */
    private $pids = [];

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
     * 延时队列对应queue绑定配置
     * @var array
     */
    private $delaySetting = [];

    /**
     * worker实例
     * @var object
     */
    private $worker;

    /**
     * consumer初始化
     * @param $consumer
     */
    private function initConsumer($consumer)
    {
        $this->consumer = ucfirst($consumer);

        $this->root = Yii::$app->params['root'];

        $this->pidfile = Yii::$app->params['queue']['pidfile_root'] . $this->consumer;

        $this->bindSetting = Yii::$app->params['queue']['C' . $this->consumer];

        $this->delaySetting = Yii::$app->params['queue']['DQueue'];

        $this->pids = file_exists($this->pidfile)
            ? explode(',', file_get_contents($this->pidfile))
            : null;

        global $logId;
        $logId = 'consumer_' . $consumer .'_' . uniqid() . mt_rand(100000, 999999);        //重置 logid

        is_dir(Yii::$app->params['queue']['pidfile_root']) ?? mkdir(Yii::$app->params['queue']['pidfile_root']);
    }

    /**
     * 启动consumer-worker
     * @param $consumer
     */
    public function actionStart($consumer)
    {
        $this->initConsumer($consumer);

        $pid = pcntl_fork();

        if ($pid == -1) {
            die('could not fork');
        } elseif ($pid) {
            exit('parent process');
        } else {
            posix_setsid();

            if (file_exists($this->pidfile)) {
                file_put_contents($this->pidfile, ',' . posix_getpid(), FILE_APPEND);
            } else {
                file_put_contents($this->pidfile, posix_getpid());
            }

            $this->msgQueue();
        }
    }

    /**
     * 停止consumer-worker
     * @param $consumer
     */
    public function actionStop($consumer)
    {
        $this->initConsumer($consumer);

        if (empty($this->pids)) {
            echo "\n" . 'worker not start' . "\n";
            exit();
        }

        foreach ($this->pids as $pid) {
            posix_kill($pid, SIGTERM);
        }

        unlink($this->pidfile);

        echo "\n" . 'Stop Success' . "\n";
    }

    /**
     * 重启consumer-worker
     * @param $consumer
     */
    public function actionRestart($consumer)
    {
        $this->actionStop($consumer);

        sleep(2);

        $this->actionStart($consumer);
    }

    /**
     * 连接MQ并消费
     */
    private function msgQueue()
    {
        if (!file_exists($this->root . '/modules/queue/workers/' . $this->consumer . '.php')) {
            echo "\n" . "workerService does not exist" . "\n";
            exit();
        }
        $workerName = 'console\\modules\\queue\\workers\\' . $this->consumer;

        $this->worker = new $workerName();
        $this->worker->run(Yii::$app->redis, $this->bindSetting, $this->delaySetting);
    }
}