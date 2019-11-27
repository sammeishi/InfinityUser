<?php
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

define('ROOT_PATH',dirname(__DIR__));
define('SRC_PATH',ROOT_PATH."/src/");


require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config =  [
    'id' => 'test-app',
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

(new yii\web\Application($config))->run();

?>