<?php
namespace imsd\infinityUser\part;
use imsd\infinityUser\common\ORMBase;
use imsd\infinityUser\User;
/*
 * 用户的登陆账号部分
 * 1个用户，可以有多个平台
 * 1个平台，可有有多个账号
* */
class Login extends ORMBase{

    /**
     * 构造一个login实例.
     * 密码处理 <br>
     *  - 传入pwd属性，hash后，生成hash_pwd属性
     *  - 传入hash_pwd属性，忽略pwd属性，直接存储使用
     * @param array $init 初始化数据
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($init = []){
        //hash_pwd为空，则使用pwd属性生成
        if( empty($init['hash_pwd']) ){
            if( !empty($init['pwd']) ){
                $init['hash_pwd'] = self::hash( $init['pwd'] );
            }
        }
        //构造
        parent::__construct($init);
    }

    /**
     * 与User进行关联.
     * 仅返回查询器
     * @return \yii\db\ActiveQuery
     */
    public function getUser(){
        return $this->hasOne( User::className(),array( 'uid' =>'uid' ) );
    }

    /**
     * 修改本login的密码.
     * 传入的是密码明文！
     * @param string    $pwd    要修改的密码明文
     */
    public function setPwd( $pwd ){
        $this->hash_pwd = static::hash( $pwd );
    }

    /**
     * 密码加密算法.
     * @param string    $pwd    要加密的密码明文
     * @return string           加密后的字符串,64位长
     */
    public static function hash( $pwd ){
        $salt = md5('I am Iron Man!');
        return md5( strrev( base64_encode( md5( $pwd.$salt ) ) ));
    }

    /**
     * 验证帐号密码是否是本login的.
     * @param string $account   帐号
     * @param string $pwd       密码明文
     * @return bool
     */
    public function verify( $account,$pwd ){
        return $this->account === $account && $this->hash_pwd === self::hash( $pwd );
    }
}

?>