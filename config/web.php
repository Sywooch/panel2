<?php

use yii\helpers\ArrayHelper;

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'panel2',
    'name'=>'Панель',
    'language'=>'ru',
    'basePath' => dirname(__DIR__),

    'bootstrap' => ['log'],

    'layout' => '@app/modules/core/views/layouts/admin.php',

    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'modules'=>[
        /*'user' => [
            'class'=>'app\modules\user\Module',
        ],*/
        'user' => [
            'class' => 'dektrium\user\Module',
        ],
        'core'=> [

            'class' => 'app\modules\core\Module',
        ]
    ],

    'components' => [

        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'qjnYa_W_yuARSOqWA2_Kx1uDVySXWoAp',
        ],
        'migrator'=>[
            'class'=>'\app\modules\core\components\Migrator',
        ],
        'moduleManager'=>[
            'class'=>'\app\modules\core\components\ModuleManager',
        ],
        'cache' => [
            'class' => '\yii\caching\FileCache',
            //'class' => 'yii\caching\MemCache',
        ],
        'user' => [
            'identityClass' => '\app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],

        'view'=>[
            'class' => 'app\modules\core\components\View',
        ],
    ],

    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        //'allowedIPs' => ['127.0.0.1', '::1'],
        // uncomment the following to add your IP if you are not connecting from localhost.
    ];

    $config['components']['urlManager']['rules'] = ArrayHelper::merge(
    [
        'gii' => 'gii',
        'gii/<controller:\w+>' => 'gii/<controller>',
        'gii/<controller:\w+>/<action:\w+>' => 'gii/<controller>/<action>',
    ], $config['components']['urlManager']['rules']);
}


return $config;
