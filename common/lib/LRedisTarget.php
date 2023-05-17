<?php

namespace common\lib;

use yii\redis\Connection as redisConnection;
use common\misc\LUtil;
use Yii;
use yii\log\Logger;
use yii\log\Target;
use yii\helpers\VarDumper;

/**
 * Class LRedisTarget
 * @package common\lib
 */
class LRedisTarget extends Target
{
    /** @var redisConnection * */
    public $redisProducer;

    /**
     * @var string
     */
    public $queue_name;

    /**
     * @var array
     */
    public $levels_arr;

    /**
     * @param array|int $levels
     * @throws \yii\base\InvalidConfigException
     */
    public function setLevels($levels)
    {
        $this->levels_arr = $levels;

        parent::setLevels($levels);
    }

    /**
     * init
     */
    public function init()
    {
        parent::init();

        $this->redisProducer = Yii::$app->get("redis");
    }

    /**
     * Exports log [[messages]] to a specific destination.
     * Child classes must implement this method.
     */
    public function export()
    {
        $text = array_map([$this, 'formatMessage'], $this->messages);
        foreach($text as $one) {
            if (empty($one)){
                continue;
            }

            try {
                $this->redisProducer->lpush($this->queue_name, json_encode($one, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $e) {
                Yii::error("msg-1[" . $e->getMessage() . "] data" . json_encode($one, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "");
                try {
                    $this->redisProducer->lpush($this->queue_name, $text);
                } catch (\Throwable $e) {
                    Yii::error("msg-2[" . $e->getMessage() . "] data" . json_encode($one, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "");
                }
            }
        }
    }

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return array|string
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!in_array($level, $this->levels_arr)) {
            return [];
        }

        $indexname = "logs";
        $errorindexname = "logs-error";

        global $logId;
        global $step;

        $data = [
            "log_id" => $logId,
            "indexname" => $level == 'error' ? $errorindexname : $indexname,
            "time" => $this->getTime($timestamp),
            "category" => $category,
            "level" => $level,
            "step" => $step++,
        ];

        static $hostname = NULL;
        if (is_null($hostname)) {
            $hostname = gethostname();
        }
        $data['local_hostname'] = $hostname ?: 'unknown';

        if (!LUtil::isCli()) {
            $data["ip_address"] = LUtil::getRealAddress();
        }

        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string)$text;
                $data['msg'] = $text;
            } else if (is_array($text)) {
                if (isset($text['log_id'])) {
                    unset($text['log_id']);
                }
                $data = array_merge($data, $text);
            } else {
                $data['msg'] = VarDumper::export($text);
            }
        } else {
            $data['msg'] = $text;
        }

        $data['msg'] = (string)$data['msg'];
        if (strlen($data['msg']) > 512) {
            $data['msg'] = mb_substr($data['msg'], 0, 512);
        }

        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }
        if ($traces) {
            $data['traces'] = $traces;
        }

        if (defined('ENV') && ENV === 'pre' && isset($data['indexname'])) {
            $data['indexname'] = ENV . '_' . $data['indexname'];
        }

        return $data;
    }
}