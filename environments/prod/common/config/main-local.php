<?php

require __DIR__ . '/env.php';

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_DATABASE_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'dsn' => getenv('MAILER_DSN') ?: 'null://null',
            ],
        ],
        //'session' => [
        //'timeout' => 300, //acá colocas el tiempo en segundos
        //'class' => 'yii\web\DbSession',
        //'sessionTable' => 'YiiSession',
        //],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => false,
        ],
    ],
];