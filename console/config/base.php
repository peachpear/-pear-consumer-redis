<?php
return array(
	'name' => 'demo',
	'id'   =>   "demo-console",
	'basePath' => dirname(__DIR__),
    'controllerNamespace'   =>  "console\controllers",
    'aliases' => [
        '@console' => realpath(__DIR__."/../"),
    ],
    'modules' => [
        'script' => 'console\modules\script\Module',
        'queue' => 'console\modules\queue\Module',
    ],
    "components" =>  [
        'log' => [
            'targets' => [
                'queue' => [
                    'levels' => ['error', 'warning'],
                ],
                'file' => [
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'class' => 'console\components\LConsoleErrorHandler',
        ],
    ],
    'params' => [
        'queue' => [
            'CLog' => [
                'name' => 'queue_logs',
                'ttr' => 5,
                'attempts' => 5,
            ],
            'CMail' => [
                'name' => 'queue_mail',
                'ttr' => 60,
                'attempts' => 3,
            ],
            'CTicket' => [
                'name' => 'queue_ticket',
                'ttr' => 60,
                'attempts' => 3,
            ],
            'DQueue' => [
                'name' => 'queue_delay',
            ],
        ],
    ],
);