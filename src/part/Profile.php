<?php
namespace imsd\infinityUser\part;
use imsd\infinityUser\common\ORMBase;
use imsd\infinityUser\User;
/*
 * 用户的登陆账号部分
 * 1个用户，可以有多个平台
 * 1个平台，只能有1个账号
 * */
class Profile extends ORMBase{
    /*
     * ==============================
     * 申明与User的ORM关系
     * ==============================
     * */
    public function getUser(){
        return $this->hasOne( User::className(),array( 'uid' =>'uid' ) );
    }
}
?>