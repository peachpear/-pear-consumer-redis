<?php
use common\misc\LEnv;

defined('YII_DEBUG') or define("YII_DEBUG", LEnv::get('APP.debug'));

$initConfig = [
    "components"  =>  [

    ],
    "params" => [
        'root' => dirname(__DIR__),
        'queue' => [
            'pidfile_root' => LEnv::get('queue.pidfile_root') ?? (dirname(__DIR__) .'/runtime/queue/'),  // /var/www/log/rabbitMQ/prod/
        ],
    ],
];
list($commonBaseConfig, $commonConfig) = include(__DIR__ . '/../../common/config/main.php');
$baseConfig = include('base.php');

return [$commonBaseConfig, $commonConfig, $baseConfig, $initConfig];
