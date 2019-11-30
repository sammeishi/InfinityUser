<?php
namespace imsd\infinityUser;
use imsd\infinityUser\Login;
use imsd\infinityUser\ORMBase;
use imsd\infinityUser\Config;
use imsd\infinityUser\Profile;
use imsd\infinityUser\GroupStore;
use imsd\infinityUser\RoleStore;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

/*
 * 用户类
 * 实现用户相关的常用功能，并提供扩展方式。
 * 可以基于此类派生出如：“职员系统” “会员系统”
 * @零件 part
 * 用户模型太大在考虑到扩展性，将功能分割成独立的ORM对象，使用ActiveRecord的
 * ORM关联功能进行融合。
 * @零件必须通过get定义
 *  ActiveRecord中通过定义 getPartName函数方式来定义与主ORM的关联性（hasOne,hasMany）
 *  不能私自挂载，如 User['xxx'] = new part()
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
    //此ORM的默认空间，存储时会使用
    public static $SPACE = null;
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
    public static function constructCheck( $init = [] ){
        if( !is_array($init) ){
            throw new Exception('construct config wrong! need be array!');
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
     * 完整构造出一个新的User实例
     * 将零件全部构造出实例（link）
     * @零件初始化参数
     *  主初始化参数内，包含了以零件key为索引的零件初始化参数
     * @自动剔除无用参数
     *  初始化参数的无用参数会被删除，ORMBase中调用了 delUnknownProp
     * */
    public static function create( $allInit = array() ){
        //不传入空间使用继承类覆盖的值
        !isset($allInit['space']) ? $allInit['space'] = static::$SPACE : null;
        //构造出User实例并立即保存，否则零件无法link（User不存在，link时没有uid的）
        $user = new static( $allInit );
        $user->save();
        //创建各个零件
        $partList = static::createAllPart( $allInit );
        //在事务内插入各个零件 link调用立即insert数据库
        static::transaction( function()use( $user,$partList ){
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
     * */
    public static function createAllPart( $allInit ){
        $list = array(); //所有零件
        //常规零件定义
        $partDefine = [
            'profile' => Profile::className()
        ];
        //构造每个零件的实例
        foreach ( $partDefine as $name => $partClass){
            $init = isset( $allInit[$name] ) ? $allInit[ $name ] : null;
            $list[ $name ] = new $partClass( $init );
        }
        //返回所有零件
        return $list;
    }
    /*
     * 监听beforeDelete，删除各个零件
     * ActiveRecord的delete只会删除自身，并不会删除link的各个ORM,因此此处删除零件
     * @自动事务
     *  delete已经开启了事务，因此不需要单独开启！
     * @触发deleteExtPart
     *  会调用deleteExtPart，继承类可以删除扩展零件
     * */
    public function beforeDelete(){
        //删除profile
        Profile::deleteAll(['uid' => $this->uid]);
        //删除所有login
        Login::deleteAll(['uid' => $this->uid]);
        //返回true继续删除User自身
        return true;
    }
    /*
     * ======================================================
     * 资料零件
     * ======================================================
     * */
    /*
     * 资料的定义
     * @return ActiveQuery  仅返回查询器，不要返回结果。查询器可以串联使用。
     */
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
     * 登录薄的定义
     * 登陆薄是指用户的所有登陆信息
     * @loginBook结构
     *  [ login instance,login instance,login instance ... ]
     * @return  ActiveQuery     仅返回【查询器】
     * */
    public function getLoginBook(){
        return $this->hasMany(Login::className(),['uid' => 'uid']);
    }
    /*
     * 从参数构造一个登陆薄
     * */
    public static function newLoginBook( $init ){
        $init = is_array( $init ) ? $init : [];
        $loginBook = [];
        foreach ( $init as $loginInit ){
            $loginBook[] = new Login( $loginInit );
        }
        return $loginBook;
    }
    /*
     * 查询指定平台的指定账号登陆信息
     * 一个user有多个login，因此不能默认给个login，要精确的去查询
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
    /*
     * 更新登陆薄
     * 1. 先删除所有登陆信息
     * 2. 在将登陆信息全部link进去
     * @param   array   $loginBook  登陆薄实例。如果为空，则清空当前所有登陆信息
     * */
    public function applyLoginBook( $loginBook ){
        $user = $this;
        self::transaction(function()use($loginBook,$user){
            //删除当前所有login信息
            Login::deleteAll( ['uid' => $user->uid] );
            //将新的插入
            foreach ( $loginBook as $login ){
                $login->link('user',$user);
            }
        });
    }
    /*
     * =====================================================
     * 工具相关
     * =====================================================
     * */
    /*
     * 转换为数组
     * 遍历各个零件
     * @param   array | null    $specify仅允许的零件被转换为数组，null为全部零件
     * */
    public function asArray( $specify = null ){
        //通过get获取的零件,此处定义的都在User上对应了getter
        $partList = ['profile','loginBook'];
        //转换自身
        $res = ArrayHelper::toArray($this);
        //遍历获取零件，会触发sql查询！然后转换为Array
        foreach ( $partList as $partName ){
            if( !$specify || isset( $specify[ $partName ] ) ){
                $res[ $partName ] = ArrayHelper::toArray($this->{$partName});
            }
        }
        return $res;
    }
}