<?php

use common\misc\LEnv;

defined("ENV") || define("ENV", LEnv::get('APP.env'));
$baseConfig = include('base.php');

$commonConfig = array(
    'components' => [
        'log' => [
            'flushInterval' => 10,
            'traceLevel' => YII_DEBUG ? 3 : 0,
        ],
        'peachDB' => [
            'dsn' => 'mysql:host=' .LEnv::get('peachDB.host') .';port=' .LEnv::get('peachDB.port') .';dbname=' .LEnv::get('peachDB.dbname'),
            'username' => LEnv::get('peachDB.username'),
            'password' => LEnv::get('peachDB.password'),
        ],
        'redis' => [
            'hostname' => LEnv::get('redis.hostname'),
            'port' => LEnv::get('redis.port'),
            'database' => LEnv::get('redis.database'),
        ],
        'mailer' => [
            'transport' => [
                'username' => LEnv::get('mailer.transport.username'),
                'password' => LEnv::get('mailer.transport.password'),
            ],
            'messageConfig' => [
                'from' => [LEnv::get('mailer.messageConfig.from.key') => LEnv::get('mailer.messageConfig.from.value')]
            ],
        ],
    ],
    'params' => [
        'ticket' => [
            'api_url' => 'http://dev.demo.com',
            'api_secret' => 'devf6bcd4341d373cade4e832456b4f7',
        ],
    ],
);

return [$baseConfig, $commonConfig];
