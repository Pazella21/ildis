<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'name' => 'Jaringan Dokumentasi dan Informasi Hukum',
    'bootstrap' => ['log', 'userCounter', 'visitorCounter'],
    'controllerNamespace' => 'frontend\controllers',
    'modules' => [
        'gridview' =>  [
            'class' => '\kartik\grid\Module',
        ],


    ],
    'components' => [

        'userCounter' => [
            'class' => 'app\components\UserCounter',

            // You can setup these options:
            'tableUsers' => 'pcounter_users',
            'tableSave' => 'pcounter_save',
            'autoInstallTables' => false,
            'onlineTime' => 10, // min
        ],

        'visitorCounter' => [
            'class' => 'common\components\VisitorCounter',
            'deduplicateWindowMinutes' => 30,
            'cookieName' => '__visitor_id',
            'cookieExpiryDays' => 180,
        ],

        'view' => [
            'class' => 'daxslab\taggedview\View',
            
        ],
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\Member',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true, 'secure' => getenv('YII_ENV') === 'prod', 'sameSite' => 'Lax'],
        ],
        'session' => [
            'name' => 'ildis-frontend',
            'cookieParams' => [
                'httponly' => true,
                'secure' => getenv('YII_ENV') === 'prod',
                'sameSite' => 'Lax',
            ],
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
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => array_merge(
                require __DIR__ . '/../../common/config/document-url-rules.php',
                [
                    'dokumen-pembentukan-puu' => 'dokumen/legislation-formation',
                    'dokumen-pembentukan-puu/<slug:[\w-]+>' => 'dokumen/legislation-formation',
                    '/' => 'site/index',
                    'kontak' => 'site/kontak',
                    'statistik' => 'statistik/index',
                    'survey-kepuasan' => 'site/survey',
                    'ikm' => 'site/survey',
                    'sitemap.xml' => 'sitemap/index',
                    '/rancangan' => 'rancangan/index',
                ]
            ),
        ],
    ],
    'params' => $params,


];
