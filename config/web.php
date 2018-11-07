<?php
/**
 * @return string
 */
function dump($data)
{
    \yii\helpers\VarDumper::dump($data, 10, true);
}

/**
 * @return \yii\web\User
 */
function user()
{
    return Yii::$app->user;
}

/**
 * @return \app\models\User
 */
function identity()
{
    return user()->identity;
}

/**
 * @return \yii\web\Request
 */
function request()
{
    return Yii::$app->request;
}

function dumpQuery(\yii\db\Query $query)
{
    echo '<pre style="width:600px;">';
    echo ($query->prepare(\Yii::$app->db->queryBuilder)->createCommand(\Yii::$app->db)->rawSql);
    echo '</pre>';
}

Yii::setAlias('@storage', dirname(__DIR__) . '/storage');

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'name' => 'Filmun',
    'language' => 'ru-RU',
    'sourceLanguage' => 'ru-RU',
    'timeZone' => 'Europe/Kiev',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'v6PZ-Q6eQ-nHITmZ-T7mhCyP46gqEvHQ',
            'scriptUrl' => '/index.php',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => '_filmsUser',
            ]
        ],
        'telegram' => [
            'class' => 'app\components\Telegram',
            'botToken' => '348426064:AAH4pWS7hH8TpFPRBSliIKjtlC51p0X8PXs',
            'botUsername' => 'filmun_bot',
        ],
        'authManager' => [
            'class' => 'app\components\PhpManager',
            'itemFile' => '@app/rbac/data/items.php',
            'ruleFile' => '@app/rbac/data/rules.php',
            'assignmentFile' => '@app/rbac/data/assignments.php',
            'defaultRoles' => ['guest'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
                [
                    'pattern' => 'film/movies',
                    'route' => 'film/index',
                    'defaults' => ['is_series' => 0],
                ],
                [
                    'pattern' => 'film/series',
                    'route' => 'film/index',
                    'defaults' => ['is_series' => 1],
                ],
                'api/<controller>/<id:\d+>/<action>' => 'api/<controller>/<action>',
            ]
        ],
        'jwt' => [
            'class' => 'app\components\Jwt',
            'key'   => 'b7$K]3z:ju^y~@!U',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@app/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.yandex.ru',
                'username' => 'noreply@filmun.net',
                'password' => 'Ptb4Xu6wpM',
                'port' => '25',
                'encryption' => 'tls',
            ],
        ],
        'assetManager' => [
            //'forceCopy' => true,
            'bundles' => [
                'yii\bootstrap\BootstrapAsset' => [
                    'basePath' => '@webroot',
                    'baseUrl' => '@web',
                    'css' => [
                        'bootstrap/css/bootstrap.css'
                    ]
                ],
                'yii\jui\JuiAsset' => [
                    'js' => [
                        'jquery-ui.js',
                        'ui/i18n/datepicker-ru.js',
                    ]
                ],
                'yii\bootstrap\BootstrapPluginAsset' => [
                    'depends' => [
                        'yii\web\JqueryAsset',
                        'yii\jui\JuiAsset',
                        'yii\bootstrap\BootstrapAsset',
                    ],
                ],
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                /*[
                    'class' => 'yii\log\EmailTarget',
                    'mailer' =>'mailer',
                    'levels' => ['error'],
                    'message' => [
                        'from' => ['info@recrm.com.ua'],
                        'to' => ['pasechnikbs@gmail.com'],
                        'subject' => 'base.recrm.com.ua - error',
                    ],
                ],*/
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'amazons3' => [
            'class' => 'app\components\AmazonS3',
            'key'    => 'AKIAIKW3BIQVLZ7ZOBWA',
            'secret' => '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',
            'bucket' => 'bucket-e0d5cbae',
            'region' => 'us-east-1',
            'path' => 'filmun/',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    /*$config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['188.163.21.159'],
    ];*/

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
