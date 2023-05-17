<?php

use common\misc\LEnv;

defined("APP_NAME") || define("APP_NAME", LEnv::get('APP.name'));
defined('VERSION') or define('VERSION', LEnv::get('APP.version'));

return array(
    'aliases' => [
        '@common' => realpath(__DIR__ . "/../"),
    ],
    'bootstrap' => ['peachDB','log'],
    'components' => [
        'log' => [
            'targets' => [
                'queue' => [
                    'class' => 'common\lib\LRedisTarget',
                    'exportInterval' => 1,
                    'queue_name' => 'queue_logs',
                ],
                'file' => [
                    'class' => 'common\lib\LFileTarget',
                    'exportInterval' => 10,
                ],
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'peachDB' => [
            'class' => '\yii\db\Connection',
            'charset' => 'utf8mb4',
            'enableQueryCache' => false,
        ],
        'curl' => [
            'class' => 'common\components\LComponentCurl',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,  // false发送邮件；true只是生成邮件在runtime文件夹下，不发邮件
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.exmail.demo.com',  // 每种邮箱的host配置不一样
                'port' => '465',
                'encryption' => 'ssl',
            ],
            'messageConfig' => [
                'charset' => 'UTF-8',
            ],
        ],
    ]
);
