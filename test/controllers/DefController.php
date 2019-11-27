<?php
namespace app\controllers;
use yii\web\Controller;
use imsd\infinityUser\Config;

class DefController extends Controller
{
    public function actionIndex()
    {
        print_r(new Config());
        return 'i ama def!!';
    }
}