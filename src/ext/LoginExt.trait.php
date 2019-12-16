<?php
namespace imsd\infinityUser\ext;
use imsd\infinityUser\part\Login;
use imsd\infinityUser\User;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 * 登陆相关功能的扩展.
 *  - 扩展登录功能，如查询，验证
 *  - 扩展登录薄
 * 登录薄<br>
 * 一个用户所有的登录信息存储在集合内，这个集合就是登录薄.<br>
 * 登陆项限制<br>
 * 一个用户可以由多个平台(但不能重复)<br>
 * 一个平台下，仅能有一个账户
 */
trait LoginExt{

    /**
     * 查询登录项.
     * 可通过平台，帐号查询 <br>
     * @param string    $platform   要查询的平台
     * @param null      $account    平台上帐号
     * @param bool      $onlyOne    是否只查询一个
     * @return login | array        指定onlyOne返回一个login实例，否则返回数组 array[ login1,login2 ]
     */
    public function queryLogin( $platform,$account = null,$onlyOne = false ){
        $user = $this;
        $account = $account != null && !is_array( $account ) ? [ $account ] : $account;
        $condition = [ 'platform' => $platform ];
        $account ? $condition['account'] = $account : null;
        $q = $user->hasMany( Login::className(), ['uid' => 'uid'])
            ->where( $condition );
        return $onlyOne ? $q->one() : $q->all();
    }

    /**
     * 验证登录帐号密码
     * @param string    $platform   要验证的登录平台
     * @param string    $account    帐号
     * @param string    $pwd        密码
     * @return bool
     */
    public function verifyLogin( $platform,$account,$pwd ){
        $login = $this->queryLogin( $platform,$account,true );
        return $login ? $login->verify( $account,$pwd ) : false;
    }

    /**
     * 登录薄关联.
     * 此关联可以一次性获取所有登录信息，即登录薄.<br>
     * 结构<br>
     *  [ login1,login2,loginN ... ]
     * @return \yii\db\ActiveQuery  仅返回关联查询器
     * */
    public function getLoginBook(){
        return $this->hasMany(Login::className(),['uid' => 'uid']);
    }

    /**
     * 工具方法：创建一个新的登录薄.
     * 仅仅创建login实例，并不会插入！<br>
     * 密码处理 <br>
     * pwd属性是明文密码，最终会hash后存储到属性 hash_pwd上。<br>
     * 此处理交由login构造器实现
     *  - 传入pwd属性，hash后，生成hash_pwd属性
     *  - 传入hash_pwd属性，忽略pwd属性，直接存储使用
     * @param array $allInit login实例初始化参数
     * @return  array
     * @throws InvalidConfigException
     * @throws \Exception
     */
    public static function createLoginBook( $allInit = [] ){
        $book = [];
        $repeatTest = [];
        foreach ( $allInit as $loginInit ){
            $login = new Login( $loginInit );
            $key = $login->platform."_".$login->account;
            if( in_array( $key,$repeatTest ) ){
                throw new Exception($key . " repeat!");
            }
            $book[] = $login;
            $repeatTest[] = $key;
        }
        return $book;
    }

    /**
     * 替换一个用户的登录薄.
     * 会删除用户当前所有登录信息，再将新的登录信息插入
     * @param array          $loginBook  登录薄实例
     * @throws \Throwable
     */
    public function replaceLoginBook( $loginBook ){
        $user = $this;
        User::transaction(function()use($loginBook,$user){
            //删除当前所有login信息
            Login::deleteAll( ['uid' => $user->uid] );
            //将新的插入
            foreach ( $loginBook as $login ){
                $login->link('user',$user);
            }
        });
    }

    /**
     * 清空用户的登录薄.
     * 一般不用，主要是测试使用
     */
    public function emptyLoginBook(){
        //删除当前所有login信息
        Login::deleteAll( ['uid' => $this->uid] );
    }

}