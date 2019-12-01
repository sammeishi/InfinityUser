<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ORMBase;
use imsd\infinityUser\User;
/*
 * 用户的登陆账号部分
 * 1个用户，可以有多个平台
 * 1个平台，可有有多个账号
 * */
class Login extends ORMBase{
    /*
     * ==============================
     * 申明与User的ORM关系
     * ==============================
     * */
    public function getUser(){
        return $this->hasOne( User::className(),array( 'uid' =>'uid' ) );
    }
    /*
     * 设置密码
     * */
    public function setPwd( $pwd,$useHash = true ){
        $this->pwd = $useHash ? static::hash( $pwd ) : $pwd;
    }
    /*
     * 对密码hash加密
     * */
    public static function hash( $pwd ){
        $salt = md5('I am Iron Man!');
        return md5( strrev( base64_encode( md5( $pwd.$salt ) ) ));
    }
    /*
     * 验证传入账号密码是否等于自身
     * */
    public function test( $account,$pwd ){
        return $this->account === $account && $this->pwd === self::hash( $pwd );
    }
}

?>