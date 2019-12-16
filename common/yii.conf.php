<?php
/*
 * yii初始化程序
 * 载入autoloader
 * 载入yii主程序
 * */

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(dirname( __DIR__ ) . '/vendor/autoload.php');
require(dirname( __DIR__ ) . '/vendor/yiisoft/yii2/Yii.php');

$config =  [
    'id' => 'test-web-app',
    // the basePath of the application will be the `micro-app` directory
    'basePath' => __DIR__,
    'defaultRoute'=>'def/index',
    'components' => [
        // ...
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=infinityUser',
            'username' => 'root',
            'password' => '123456Abc!',
            'charset' => 'utf8',
        ]
    ],
];

return $config;

?>