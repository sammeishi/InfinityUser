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
     * 保存前，对密码进行md5
     * 藏属性中有pwd才会对密码进行hash
     * 藏属性中没有说明没有设置密码
     * */
    public function beforeSave( $insert ){
        $res = $this->getDirtyAttributes(['pwd']);
        if( $res && isset($res['pwd']) ){
            $this->pwd = static::hash( $this->pwd );
        }
        return parent::beforeSave($insert);
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