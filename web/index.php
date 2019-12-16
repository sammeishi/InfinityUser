<?php
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
$yiiConf = require dirname(__DIR__) . "/common/yii.conf.php";
$yiiConf['basePath'] = __DIR__;
(new yii\web\Application($yiiConf))->run();
?>