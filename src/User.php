<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ORMBase;
use imsd\infinityUser\Config;
use imsd\infinityUser\Profile;
use imsd\infinityUser\Login;
/*
 * 用户类
 * 实现用户相关的常用功能，并提供扩展方式。
 * 可以基于此类派生出如：“职员系统” “会员系统”
 * @零件 part
 * 用户模型太大在考虑到扩展性，将功能分割成独立的ORM对象，使用ActiveRecord的
 * ORM关联功能进行融合。
 * @ActiveRecord的禁止2个未保存ORM进行link
 *  如果new user之后没有save，零件link这个user，会报错！
 *  因为没有ID进行join呀！！
 *  请确保user立即save之后在link！
 * @零件列表
 *  1. 主体       User    用户主体，包含uid，状态以及其他部分id的指引等
 *  2. 资料       Profile 用户姓名，性别，生日等
 *  3. 登陆信息   login   用户在每个平台账号密码。可有多平台单1平台仅1账号
 * */
class User extends ORMBase{
    /*
     * ======================================================
     * 用户状态
     * 小于1的状态为无效，用户被禁用
     * 大于等于1的都正常使用中
     * ======================================================
     * */
    public static $STATUS_USABLE = 1; //正常
    public static $STATUS_DISABLE = 0; //被禁用所有功能被停用，但可被恢复
    public static $STATUS_INTENDED = -1; //预注册用户，功能被限制。可被彻底删除
    public static $STATUS_INVALID = -99999; //已注销，彻底无效
    /*
     * 默认构造
     * 仅提供User实例的new，并不会构造零件！
     * */
    public function __construct($init = []){
        parent::__construct( $init );
        //状态为空，使用默认值
        $this->status === null ? $this->status = self::$STATUS_USABLE : null;
    }
    /*
     * 完整构造
     * 完整的构造出一个User实例
     * 会将零件全部构造出
     * @零件初始化
     *  主初始化参数内，包含了以零件key为索引的零件初始化参数
     * */
    public static function create( $allInit = array() ){
        //先构造零件：profile
        list( $partList, $init ) = self::createAllPart( $allInit );
        //构造出User并立即保存，
        $user = new self( $init );
        $user->save();
        //在事务内使用link链接零件
        self::transaction( function()use( $user,$partList ){
            foreach ($partList as $part){
                $part->link('user',$user);
            }
        } );
        //返回user
        return $user;
    }
    /*
     * 构造出零件列表实例
     * @会修改主配置
     *  将主配置中零件配置删除，只保留主配置
     * */
    public static function createAllPart( $allInit ){
        $list = array();
        $define = array(
            'profile' => Profile::className()
        );
        foreach ($define as $name => $partClass){
            $init = isset( $allInit[$name] ) ? $allInit[ $name ] : null;
            $list[ $name ] = new $partClass( $init );
            if( $init ){
                unset( $allInit[$name] );
            }
        }
        return [ $list,$allInit ];
    }
    /*
     * ======================================================
     * 资料零件
     * ======================================================
     * */
    public function getProfile(){
        return $this->hasOne(Profile::className(),['uid' => 'uid']);
    }
    /*
     * ======================================================
     * 登陆零件
     * @与User关系
     *  1 User hasMany Login
     * @同平台唯一
     *  一个用户只能含有一个相同平台
     * @每平台只能一个账号
     *  每个平台只能含有一个账号。
     * ======================================================
     * */
    /*
     * 查询指定平台的指定账号登陆信息
     * @return  login    返回查询出的login实例
     * */
    public function getLoginBook( $platform ){
        return $this->hasOne( Login::className(), ['uid' => 'uid'])
            ->where( ['platform' => $platform] )
            ->all();
    }
    /*
     * 更新(增加，修改)用户的某一个登陆信息
     * 如果此登陆存在，则更新
     * 如果不存在，则插入
     * */
    public function updateLogin( $login ){
        //先查询添加的login平台是否存在
        $exist = $this->queryLogin( $login->platform );
        //如果存在，使用存在的
        if( $exist ){
            $copyAttr = ['account','pwd'];
            foreach ($copyAttr as $attr){
                $exist->{$attr} = $login->{$attr};
            }
            $login = $exist;
        }
        //连接到自身User上
        $login->link('user',$this);
        //返回实例
        return $login;
    }
}