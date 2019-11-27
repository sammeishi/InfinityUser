<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ORMBase;
use imsd\infinityUser\User;
/*
 * 用户的登陆账号部分
 * 1个用户，可以有多个平台
 * 1个平台，只能有1个账号
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
     * 构造
     * 对密码进行hash
     * */
    public function __construct($config = []){
        parent::__construct($config);
        $this->setPwd( $this->pwd );
    }
    /*
     * 设置密码
     * 对密码hash后保存
     * */
    public function setPwd( $val ){
        $this->pwd = self::hash( $val );
    }
    /*
     * 对密码hash加密
     * */
    public static function hash( $pwd ){
        $salt = md5('I am Iron Man!');
        return md5( base64_encode( md5( $pwd.$salt ) ) );
    }
    /*
     * 验证传入账号密码是否等于自身
     * */
    public function test( $account,$pwd ){
        return $this->account === $account && $this->pwd === self::hash( $pwd );
    }
}

?>