<?php
namespace imsd\infinityUser;
use imsd\infinityUser\Login;
use imsd\infinityUser\ORMBase;
use imsd\infinityUser\Config;
use imsd\infinityUser\Profile;
use imsd\infinityUser\GroupStore;
use imsd\infinityUser\RoleStore;
use yii\db\Exception;

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
     * new构造
     * 检查参数正确性
     * */
    public function __construct($config = [])
    {
        static::check( $config );
        parent::__construct($config);
    }

    /*
     * 初始化 ( 由yii调用 )
     * 设置默认状态，以及各种默认值
     * */
    public function init(){
        //父init
        parent::init();
        //状态为空，使用默认值
        $this->status === null ? $this->status = self::$STATUS_USABLE : null;
    }
    /*
     * 检查构造时参数的正确性
     * 不正确直接抛出异常
     * 1. 组ID是否存在
     * 2. 角色id是否存在
     * */
    public static function check( $init = [] ){
        if( !is_array($init) ){
            throw new Exception('params not array!');
        }
        //检查角色是否存在
        if( !empty( $init['role_id'] ) ){
            $roleStore = new RoleStore( $init['space'] );
            if( $roleStore->getById( $init['role_id'] ) === null ){
                throw new Exception('role id '.$init['role_id'].' not exist!');
            }
        }
        //检查组
        if( !empty( $init['group_id'] ) ){
            $groupStore = new GroupStore( $init['space'] );
            if( $groupStore->getById( $init['group_id'] ) === null ){
                throw new Exception('group id '.$init['group_id'].' not exist!');
            }
        }
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
        $user = new static( $init );
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
     * 构造出主要零件列表实例 （还有扩展零件，继承类用自己零件时覆盖）
     * @param   $allInit    array(key=>val) 构造User传入的主配置
     * @会修改主配置
     *  零件配置也包含在主配置中，key为零件名即是
     *  因此零件构造后就会删除掉
     * */
    public static function createAllPart( $allInit ){
        $list = array();
        $define = array_merge(array(
            //零件定义列表
            'profile' => Profile::className(), //资料
        ),static::createExtPart());
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
     * 创建扩展零件
     * 方便继承类扩展自己的零件
     * @需要被继承
     * */
    public static function createExtPart(){
        return array();
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
     * @多平台多账户
     *  每个用户可以有多个平台 platform允许重复
     *  每个平台，只能有一个账号 platform > account 不能重复！
     * ======================================================
     * */
    /*
     * 此处定义的是 登录薄
     * 即这个用户所有登陆信息！！！
     * 将会查询这个用户所有登陆信息
     * */
    public function getLoginBook(){
        return $this->hasMany( Login::className(), ['uid' => 'uid']);
    }
    /*
     * 查询指定平台的指定账号登陆信息
     * @param   string          $platform   查询平台
     * @param   string/array    $account    平台的账号
     * @param   boolean         $onlyOne    是否只查询1个
     * @return  null            查询不出
     * @return  array           返回查询出的login实例，可能多个
     * */
    public function queryLogin( $platform,$account = null,$onlyOne = false ){
        $account = $account && !is_array( $account ) ? [ $account ] : $account;
        $condition = [ 'platform' => $platform ];
        $account ? $condition['account'] = $account : null;
        $q = $this->hasMany( Login::className(), ['uid' => 'uid'])
            ->where( $condition );
        return $onlyOne ? $q->one() : $q->all();
    }
    /*
     * 更新(增加，修改)用户的某一个登陆信息
     * 如果此登陆存在，则更新
     * 如果不存在，则插入
     * */
    public function updateLogin( $loginInit ){
        //先查询添加的login是否存在
        $exist = $this->queryLogin( $loginInit['platform'],$loginInit['account'] , 1 );
        //如果存在，使用存在的更新
        if( $exist ){
            foreach ($loginInit as $key => $val){
                $exist->{$key} = $val;
            }
            $exist->save();
            return $exist;
        }
        else{
            //连接到自身User上
            $login = new Login( $loginInit );
            $login->link('user',$this);
            //返回实例
            return $login;
        }
    }
}