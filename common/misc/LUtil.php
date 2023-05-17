<?php

namespace common\misc;

use yii;
use yii\log\Logger;

/**
 * 工具集合
 * Class LUtil
 * @package common\misc
 */
class LUtil
{
    const LOG_PREFIX = 'common.misc.LUtil.';

    /**
     * @return string
     */
    public static function getRealAddress()
    {
        if (!empty($_SERVER['RAW_REMOTE_ADDR'])) {
            $address = $_SERVER['RAW_REMOTE_ADDR'];
        } else if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            list($_SERVER["REMOTE_ADDR"]) = explode(',', $_SERVER["HTTP_CDN_SRC_IP"]);
            $address = trim($_SERVER["REMOTE_ADDR"]);
        } else if (!isset($_SERVER['REMOTE_ADDR']) || LUtil::isLAN($_SERVER['REMOTE_ADDR'])) {
            if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                list($_SERVER["REMOTE_ADDR"]) = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
                $address = trim($_SERVER["REMOTE_ADDR"]);
            }
        }

        return isset($address) ? $address : $_SERVER["REMOTE_ADDR"];
    }

    /**
     * 判断PHP运行环境检测是否为cli
     * @return bool
     */
    public static function isCli()
    {
        return php_sapi_name() == "cli";
    }

    /**
     * 判断ip是否为局域网ip
     * @param $ip
     * @return bool
     */
    public static function isLAN($ip)
    {
        $ip = ip2long($ip);
        $net_a = ip2long('10.0.0.0') >> 24;  // A类网预留ip的网络地址 10.0.0.0 ～ 10.255.255.255
        $net_b = ip2long('172.16.0.0') >> 20;  // B类网预留ip的网络地址 172.16.0.0 ～ 172.31.255.255
        $net_c = ip2long('192.168.0.0') >> 16;  // C类网预留ip的网络地址 192.168.0.0 ～ 192.168.255.255

        return $ip >> 24 === $net_a || $ip >> 20 === $net_b || $ip >> 16 === $net_c;
    }

    /**
     * 本地回环（loopback）
     * @param $ip
     * @return bool
     */
    public static function isLocal($ip)
    {
        $ip = ip2long($ip);
        $net_local = ip2long('127.0.0.0') >> 24; //本地机器预留ip的网络地址 127.0.0.0 ～ 127.255.255.255

        return $ip >> 24 === $net_local;
    }

    /**
     * 运营商内网
     * @param $ip
     * @return bool
     */
    public static function isISPNat($ip)
    {
        $ip = ip2long($ip);
        $net_local = ip2long('100.64.0.0') >> 22; //本地机器预留ip的网络地址 100.64.0.0 ～ 100.127.255.255

        return $ip >> 22 === $net_local;
    }

    /**
     * 获取随机字符串
     * @param $length
     * @return null|string
     */
    public static function getRandChar($length)
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];  // rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

    /**
     * @param $message
     * @param string $category
     */
    public static function logTrace($message, $category = 'application')
    {
        Yii::getLogger()->log($message, Logger::LEVEL_TRACE, $category);
    }

    /**
     * @param $message
     * @param string $category
     */
    public static function logInfo($message, $category = 'application')
    {
        Yii::getLogger()->log($message, Logger::LEVEL_INFO, $category);
    }

    /**
     * 保存远程图片到本地
     * @param type $url
     * @param type $file_name
     * @return boolean
     * @create by WQ
     */
    public static function saveRemoteFile($url, $file_name)
    {
        if (strpos($url, 'http') === false) {        //限制请求http|https
            return false;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        if (strpos($url, 'https') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    // https请求 不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, 20);      //20s超时

        $result = curl_exec($ch);
        $status = curl_getinfo($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($result == false || !isset($status['http_code']) || $status['http_code'] != 200) {
            Yii::error(json_encode([
                'response' => $result,
                'curl_info' => $status,
                'curl_error' => $curl_error,
                'curl_errno' => $curl_errno
            ]), __METHOD__);
            return false;
        } else {
            //日志记录
            LUtil::logTrace([
                'msg' => json_encode([
                    'url' => $url,
                    'http_code' => $status['http_code']
                ], JSON_UNESCAPED_UNICODE),
                'total_time' => $status['total_time']
            ], __METHOD__);
        }
        $fp = @fopen($file_name, 'w'); //保存的文件名称用的是链接里面的名称
        if ($fp == false) {
            return false;
        }
        fwrite($fp, $result);
        fclose($fp);
        return true;
    }

    /**
     * 邮箱验证
     * @param type $email
     * @return boolean
     * @create by WQ
     */
    public static function checkEmail($email)
    {
        if (empty($email) || !is_string($email)) {
            return false;
        }

        $regex = '/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/';
        $match = preg_match($regex, $email);

        if ($match) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 手机号验证
     * @param type $mobile
     * @return boolean
     * @create  by WQ
     */
    public static function checkMobile($mobile)
    {
        if (empty($mobile) || !is_string($mobile)) {
            return false;
        }

        $regex = '/^1[3-9][0-9]{9}$/';
        $match = preg_match($regex, $mobile);

        if ($match) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 文件扩展名
     * @param string $filename
     * @param bool $point 是否包含扩展名前的点 false 如 png  true 返回如 .png
     * @return string
     */
    public static function getFileExt($filename, $point = false)
    {
        if (empty($filename) || !is_string($filename)) {
            return '';
        } else {
            $ext = substr(strrchr($filename, '.'), $point ? 0 : 1);
            return $ext ?: '';
        }
    }

    /**
     * 唯一文件名
     * @param mixed $mix
     * @param string $filename
     * @return string
     */
    public static function fileKey($mix = '', $filename = '')
    {
        $ext = LUtil::getFileExt($filename, true);       //扩展名
        $fileKey = md5($mix . '_' . microtime() . '_' . rand(100, 999)) . $ext;
        return $fileKey;
    }

    /**
     * 去除链接协议
     * @param string $url
     * @return string
     */
    public static function cutProto($url)
    {
        $pattern = '/^https?:\/\//';
        return preg_replace($pattern, '', $url);
    }

    /**
     * 十进制转换三十六进制
     * @param $int
     * @param int $format
     * @return string
     */
    public static function enid($int, $format = 8)
    {
        $dic = array(
            0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9',
            10 => 'A', 11 => 'B', 12 => 'C', 13 => 'D', 14 => 'E', 15 => 'F', 16 => 'G', 17 => 'H', 18 => 'I',
            19 => 'J', 20 => 'K', 21 => 'L', 22 => 'M', 23 => 'N', 24 => 'O', 25 => 'P', 26 => 'Q', 27 => 'R',
            28 => 'S', 29 => 'T', 30 => 'U', 31 => 'V', 32 => 'W', 33 => 'X', 34 => 'Y', 35 => 'Z'
        );
        $arr = array();
        $loop = true;
        while ($loop) {
            $arr[] = $dic[bcmod($int, 36)];
            $int = floor(bcdiv($int, 36));
            if ($int == 0) {
                $loop = false;
            }
        }
        $arr = array_pad($arr, $format, $dic[0]);
        return implode('', array_reverse($arr));
    }

    /**
     * 三十六进制转换十进制
     * @param $id
     * @return int|string
     */
    public static function deid($id)
    {
        $dic = array(
            0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8', 9 => '9',
            10 => 'A', 11 => 'B', 12 => 'C', 13 => 'D', 14 => 'E', 15 => 'F', 16 => 'G', 17 => 'H', 18 => 'I',
            19 => 'J', 20 => 'K', 21 => 'L', 22 => 'M', 23 => 'N', 24 => 'O', 25 => 'P', 26 => 'Q', 27 => 'R',
            28 => 'S', 29 => 'T', 30 => 'U', 31 => 'V', 32 => 'W', 33 => 'X', 34 => 'Y', 35 => 'Z'
        );
        // 键值交换
        $dedic = array_flip($dic);
        // 去零
        $id = ltrim($id, $dic[0]);
        // 反转
        $id = strrev($id);
        $v = 0;
        for ($i = 0, $j = strlen($id); $i < $j; $i++) {
            $v = bcadd(bcmul($dedic[$id{$i}], bcpow(36, $i)), $v);
        }
        return $v;
    }

    /**
     * @param string $key
     * @param int $time_out
     * @return bool
     * @created by WQ
     * redis锁定参数方法
     */
    public static function getRedisLock($key, $time_out = 60)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }

        /** @var common\components\LRedisConnection $redis */
        $redis = Yii::$app->redis_config;

        //从redis里获取参数
        $val = intval($redis->incr($key));
        if (1 !== $val) {
            return false;
        }

        //设置有效期
        $redis->setTimeout($key, $time_out);

        return true;
    }

    /**
     * @param string $type
     * @param int $lock_id
     * @return array
     * @created by WQ
     * redis解锁方法
     */
    public static function releaseRedisLock($key)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }

        /** @var common\components\LRedisConnection $redis */
        $redis = Yii::$app->redis_config;

        //解锁
        $lock = $redis->delete($key);
        return $lock;
    }

    function getLocalIp()
    {
        $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
        //获取操作系统为linux类型的本机IP真实地址
        exec("ifconfig", $out, $stats);
        if (!empty($out)) {
            if (isset($out[1]) && strstr($out[1], 'addr:')) {
                $tmpArray = explode(":", $out[1]);
                $tmpIp = explode("", $tmpArray[1]);
                if (preg_match($preg, trim($tmpIp[0]))) {
                    return trim($tmpIp[0]);
                }
            }
        }
        return '127.0.0.1';
    }

    /**
     * 向下取整保留小数
     *
     * @param float $x
     * @param int $prec
     * @return void
     */
    public static function floorPrec($x, $prec)
    {
        $power = pow(10, $prec);
        return floatval(bcdiv(floor(bcmul($x, $power, 12)), $power, 2));
    }

    /**
     * 拼接链接信息
     *
     * @return string
     */
    private static function buildUrl()
    {
        $params = func_get_args();
        $head = array_shift($params);
        $tail = array_pop($params);
        $arr[] = rtrim($head, '\/\\');
        foreach ($params as $item) {
            $arr[] = trim($item, '\/\\');
        }
        if (!empty($tail)) $arr[] = ltrim($tail, '\/\\');
        return implode('/', $arr);
    }

    /**
     * java string.length()的计算，emoji表情会算为两个字符
     * @param string $str
     * @param int $length
     * @return string
     */
    public static function javaStringSub($str, $length)
    {
        $i = 0;
        $j = 0;
        $len = mb_strlen($str);

        while (true) {
            $char = mb_substr($str, $i, 1);
            if (strlen($char) >= 4) {
                $j++;
            }
            $i++;
            $j++;
            if ($j >= $length || $i >= $len) {
                break;
            }
        }
        if ($i < $len || $j > $length) {
            return mb_substr($str, 0, $i - 3) . '...';
        } else {
            return $str;
        }
    }

    /**
     * 日期差方法
     * @param int $big
     * @param int $small
     * @return int
     * @created by wt
     */
    public static function datediff($big = 0, $small = 1)
    {
        $diff = 0;

        $big = intval($big);
        $small = intval($small);

        $big_zero = strtotime(date('Ymd', $big));
        $small_zero = strtotime(date('Ymd', $small));

        $diff = intval(($big_zero - $small_zero) / 86400);

        return $diff;
    }

    /**
     * 字节大小，转换显示
     * @param int $size
     * @return string
     */
    public static function convertBitLen($size)
    {
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * 字符数量转整形数据，如 10K -> 10240
     * @param string $val
     * @return int
     */
    public static function parseAtol($val)
    {
        $last = strtolower($val[strlen($val) - 1]);
        $limit = intval($val);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
            default:
                break;
        }
        return $limit;
    }

    /**
     * @brief 正则取 url 参数
     * @param $url
     * @return array
     */
    public static function getUrlKeyValue($url)
    {
        $result = array();
        $mr = preg_match_all('/(\?|&)(.+?)=([^&?]*)/i', $url, $matchs);
        if ($mr !== false) {
            for ($i = 0; $i < $mr; $i++) {
                $result[$matchs[2][$i]] = $matchs[3][$i];
            }
        }
        return $result;
    }

    /**
     * @param $array
     * @return bool
     */
    public static function isArray($array)
    {
        if (!is_array($array)) {
            return false;
        } else {
            return array_keys($array) === range(0, count($array) - 1);
        }
    }

    /**
     * 卸载container里的组件
     *
     * @return integer 卸载的数量
     */
    public static function unloadContainer(): int
    {
        $count = 0;
        //unload container
        foreach (Yii::$container->getDefinitions() as $key => $_) {
            Yii::$container->clear($key);
            $count++;
        }
        return $count;
    }

    /**
     * @return int
     */
    public static function unloadWeb(): int
    {
        $count = 0;
        $target = [
            'yii\web\AssetManager',
            'yii\web\UrlManager',
            'yii\web\View'
        ];
        foreach (Yii::$app->getComponents() as $name => $config) {
            $class = $config['class'] ?? '';
            if (in_array($class, $target)) {
                Yii::$app->clear($name);
                $count++;
            }
        }
        return $count;
    }
}
