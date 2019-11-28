<?php
namespace app\controllers;
use imsd\infinityUser\Login;
use yii\web\Controller;
use imsd\infinityUser\User;
use imsd\infinityUser\Config;

class DefController extends Controller
{
    public function actionIndex()
    {
        User::create([
            'space' =>'test',
            'group_id' => 1,
            'login'=>[
                'platform' =>'QQ',
                'account' =>'QQ',
            ]
        ]);
    }
    public function actionAdd(){

    }
    public function actionSet(){

    }
}