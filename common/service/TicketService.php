<?php
namespace common\service;

use common\components\LComponentCurl;
use Yii;

class TicketService
{
    const SUCCESS = 200;
    const PLATFORM = "ticket";
    const RETURN_FLAG_ALL = 0;  // curl返回所有数据值

    private static $url_route = [
        'ticketRequset' => '/api/mq-callback'
    ];

    /**
     * 回调请求
     * @param array $data
     * @return bool|mixed
     */
    public static function callbackRequest($data = [])
    {
        $postData = [
            "data" => $data,
        ];
        $postData = self::hashPostFields($postData);

        $url = self::getUrl('ticketRequset');

        return self::doCurl($url, $postData);
    }

    /**
     * 获取完整url
     * @param $urlType
     * @return string
     */
    private static function getUrl($urlType)
    {
        $baseDomain = Yii::$app->params["ticket"]["api_url"];
        if (isset(self::$url_route[$urlType])) {
            return $baseDomain .self::$wo_route[$urlType];
        } else {
            return $baseDomain ."/";
        }
    }

    /**
     * post数据处理
     * @param $postData
     * @return mixed
     */
    private static function hashPostFields($postData)
    {
        $postData["time"] = (string)time();

        $api_secret = Yii::$app->params["ticket"]["api_secret"];

        $params_str = json_encode($postData);
        $postData["sign"] = hash_hmac('sha256', $params_str, $api_secret);

        return $postData;
    }

    /**
     * curl请求
     * @param $url
     * @param $postData
     * @param int $returnFlag
     * @return bool|mixed
     */
    private static function doCurl($url, $postData, $returnFlag = 1)
    {
        /** @var LComponentCurl $curl */
        $curl = Yii::$app->curl;
        $curl->setUrl($url);
        $header = ["Content-Type: application/json; charset=utf-8"];
        $rhOption = [
            CURLOPT_HTTPHEADER  =>  $header
        ];
        $curl->setOptions($rhOption);
        $curl->setPostField(json_encode($postData));

        global $logId;
        global $step; $step++;
        $log = [
            'log_id' => $logId,
            'platform' => self::PLATFORM,
            'category' => 'ticketService',
            'level' => 'info',
            'step' => $step,
            'time' => date('Y-m-d H:i:s'),
            'msg' => [
                'type' => 'request',
                'url' => $url,
                "header"    => $header,
                "body"      => $postData,
            ],
        ];
        Yii::trace($log, "application");

        $info = $curl->execute();

        $step++;
        $log = [
            'log_id' => $logId,
            'platform' => self::PLATFORM,
            'category' => 'ticketService',
            'level' => 'info',
            'step' => $step,
            'time' => date('Y-m-d H:i:s'),
            'msg' => [
                'info' => $info,
            ],
        ];
        Yii::trace($log, "application");

        if ($info !== false) {
            $rhInfo = json_decode($info, true);
            // 存在转换json错误，记录json转换错误信息
            if (JSON_ERROR_NONE != json_last_error()) {
                $step++;
                $log = [
                    'log_id' => $logId,
                    'platform' => self::PLATFORM,
                    'category' => 'ticketService',
                    'level' => 'error',
                    'step' => $step,
                    'time' => date('Y-m-d H:i:s'),
                    'msg' => "msg[json decode fail]",
                ];
                Yii::error($log, self::PLATFORM);

                return false;
            }
            // 只返回data标签
            if ($returnFlag == 1) {
                // 返回成功，直接返回
                if ($rhInfo["code"] == self::SUCCESS) {
                    return $rhInfo["data"];

                // 返回错误，写入日志
                } else {
                    $step++;
                    $log = [
                        'log_id' => $logId,
                        'platform' => self::PLATFORM,
                        'category' => 'ticketService',
                        'level' => 'error',
                        'step' => $step,
                        'time' => date('Y-m-d H:i:s'),
                        'msg' => "msg[code is not success]",
                    ];
                    Yii::trace($log, "application");

                    return false;
                }

            // 返回完整信息
            } else {
                return $rhInfo;
            }

        // curl请求失败，记录错误信息
        } else {
            $info = $curl->getErrorInfo();

            $step++;
            $log = [
                'log_id' => $logId,
                'platform' => self::PLATFORM,
                'category' => 'ticketService',
                'level' => 'error',
                'step' => $step,
                'time' => date('Y-m-d H:i:s'),
                'msg' => "msg[$info]",
            ];
            Yii::trace($log, "application");

            return false;
        }
    }

    /**
     * curl请求，限制时长
     * @param $url
     * @param $postData
     * @param int $expire
     * @return bool
     */
    private static function doCurlWithExpire($url, $postData, $expire = 3000)
    {
        /** @var LComponentCurl $curl */
        $curl = Yii::$app->curl;
        $curl->setUrl($url);
        $header = ["Content-Type: application/json; charset=utf-8"];
        $rhOption = [
            CURLOPT_HTTPHEADER  =>  $header,
            CURLOPT_TIMEOUT_MS  =>  $expire
        ];
        $curl->setOptions($rhOption);
        $curl->setPostField(json_encode($postData));

        $info = $curl->execute();
        if ($info !== false) {
            $rhInfo = json_decode($info, true);
            if (JSON_ERROR_NONE != json_last_error()) {
                // 存在转换json错误
                return false;
            }
            if ($rhInfo["code"] == self::SUCCESS) {
                return $rhInfo["data"];
            } else { // 错误信息
                return false;
            }
        } else {
            // 记录错误信息
            $info = $curl->getErrorInfo();

            return false;
        }
    }
}