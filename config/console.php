<?php
Yii::setAlias('@storage', dirname(__DIR__) . '/storage');

$params = require(__DIR__ . '/params.php');
$db = require(__DIR__ . '/db.php');

return [
    'id' => 'basic-console',
    'name' => 'Filmun.net',
    'language' => 'ru-RU',
    'sourceLanguage' => 'ru-RU',
    'timeZone' => 'Europe/Kiev',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'gii'],
    'controllerNamespace' => 'app\commands',
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'hostInfo' => 'http://filmun.net',
            'baseUrl' => '/',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\EmailTarget',
                    'mailer' =>'mailer',
                    'levels' => ['error'],
                    'message' => [
                        'from' => ['info@recrm.com.ua'],
                        'to' => ['pasechnikbs@gmail.com'],
                        'subject' => 'base.recrm.com.ua - error',
                    ],
                ],
            ],
        ],
        'db' => $db,
        'amazons3' => [
            'class' => 'app\components\AmazonS3',
            'key'    => 'AKIAIKW3BIQVLZ7ZOBWA',
            'secret' => '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',
            'bucket' => 'recrm',
            'region' => 'eu-west-1',
            'path' => '',
        ],
    ],
    'params' => $params,
];
