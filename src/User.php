<?php
namespace imsd\infinityUser;
require __DIR__ . "/ext/LoginExt.trait.php";
use imsd\infinityUser\common\ORMBase;
use imsd\infinityUser\ext\LoginExt;
use imsd\infinityUser\part\Login;
use imsd\infinityUser\part\Profile;
use yii\helpers\ArrayHelper;
/**
 * 用户实现类.
 * 此类是infinityUser的核心，它是一个框架，将各个模块引入，并实现一些重要的方法和行为！.<br>
 * 概览 <br>
 *  - 零件
 *  - 扩展
 *  - 功能
 *
 * 零件 part. <br>
 * 考虑到扩展性，将类似功能分割成独立的ORM对象，使用ActiveRecord的ORM关联功能进行融合。<br>
 * - 零件必须对应一个表<br>
 * - 零件必须与User主动关联<br>
 *  - ActiveRecord中通过定义 getPartName函数方式来定义与主ORM的关联性（hasOne,hasMany）
 *
 * 注意：先保存才能link. <br>
 * ActiveRecord的禁止2个未保存ORM进行link <br>
 * 如果new user之后没有save，零件link这个user，会报错！<br>
 * 因为没有ID进行join,请确保user立即save之后在link！<br>
 *
 * 扩展 ext. <br>
 * User的一些特性和功能并不存储表因此无法被定义为零件<br>
 * 这些额外的功能被称为扩展。它会由User引入 <br>
 * 如组，角色，登录薄都只对应1个或者0个字段，但又确有大量的功能<br>
 *
 * 扩展列表.
 * - loginBook  登录薄，包含用户所有登录信息
 * - group      组信息
 * - role       角色信息，可以判定用户拥有的权限
 *
 * 零件列表.
 *  1. 主体       User    对应 iu_user表
 *  2. 资料       Profile 对应 iu_profile 用户姓名，性别，生日等
 *  3. 登陆信息   login   对应 iu_login 一对多关系 用户在每个平台账号密码。可有多平台单1平台仅1账号
 * */
class User extends ORMBase{
    /**
     * 此类存储的目标空间.
     * 继承类必须要设置 ！
     * 继承类实现时，重载此值，将自身数据存储到对应空间.
     * @var string
     */
    public static $SPACE = null;
    /**
     * 用户状态: 正常
     * @var int
     */
    public static $STATUS_USABLE = 1;
    /**
     * 用户状态: 禁用.
     * 功能被停用，但是可以恢复。
     * @var int
     */
    public static $STATUS_DISABLE = 0;
    /**
     * 用户状态: 预分配用户.
     * 仅仅是预分配一个用户id，用于正式注册之前。
     * @var int
     */
    public static $STATUS_INTENDED = -1;
    /**
     * 用户状态：临时的
     * 会被定期删除
     */
    public static $STATUS_TMP = -10000;

    /**
     * 加载登录扩展
     */
    use LoginExt;

    /**
     * 初始化事件.
     * 如果一些属性不存在，则使用默认值
     */
    public function init(){
        //触发父类的init
        parent::init();
        //状态为空，默认为：正常
        $this->status === null ? $this->status = self::$STATUS_USABLE : null;
    }

    /**
     * 完整创建一个新的用户实例.
     * 会挨个将零件全部构造出来，使用yii的link功能.<br>
     * 传入的初始化数据，其中包含了各个零件的初始化数据，会解析出来传递给对应零件. <br>
     * 会自动删除表不存在字段
     * 流程<br>
     *  - 计算出空间名：不传入使用本类静态常量定义的
     *  - 替换状态为预分配（插入成功后在还原）
     *  - 构造出User实例，并插入。(不构造User拿不到id，就无法构造零件)
     *  - 创建各个零件 以及 还原状态
     * @param array $allInit                    全部初始化数据，包含零件的!
     * @return static                           返回User实例或者继承类实例
     * @throws \Throwable                       未知，yii抛出
     * @throws \yii\base\InvalidConfigException 未知，yii抛出
     */
    public static function create( $allInit = [] ){
        //不指明空间使用类中定义的空间
        !isset($allInit['space']) ? $allInit['space'] = static::$SPACE : null;
        //替换状态为预分配状态，插入成功在还原,防止插入失败,User自身插入没有事务
        $status = isset( $allInit['status'] ) ? $allInit['status'] : self::$STATUS_USABLE;
        //构造出User实例并立即保存，否则零件无法link（User不存在，link时没有uid的）
        $user = new static( $allInit );
        $user->status = self::$STATUS_TMP;
        $user->save();
        //创建各个零件
        $partList = static::createAllPart( $allInit );
        //在事务内插入各个零件 link调用立即insert数据库
        static::transaction( function()use( $user,$status,$partList ){
            //逐个插入零件
            foreach ($partList as $part){
                $part->link('user',$user);
            }
            //还原user状态
            $user->status = $status;
            $user->save();
        } );
        //返回user
        return $user;
    }

    /**
     * 创建所有零件.
     * 每个零件都是ORM继承类，使用yii的关联特性进行同时创建插入表. <br>
     * 仅仅是构造出实例，交由User进行link才能真正插入
     * @param   array   $allInit    User完整初始化数据，也包含各个零件的数据
     * @return  array               返回零件对象列表
     */
    public static function createAllPart( $allInit ){
        $list = array();
        //零件定义
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

    /**
     * 拦截yii事件实例删除之前,清空零件数据.
     * User删除时候会触发，但是只能删除User自身无法删除零件.<br>
     * 因此在此事件回调中删除各个零件 <br>
     * 此回调执行删除零件，也会被计入事务内，因为yii的delete已经开启了事务
     * @return bool
     */
    public function beforeDelete(){
        //删除所有零件
        self::delAllPart( $this->uid );
        //必须返回true，否则会终止删除操作。
        return true;
    }

    /**
     * 删除一个或者多个用户的所有零件.
     * 无需构造出User实例即可删除.
     * @param int | array   $uid    用户id,也可以传入数组批量删除
     */
    public static function delAllPart( $uid ){
        //删除profile,调用yii的AR方法
        Profile::deleteAll(['uid' => $uid]);
        //删除所有login,调用yii的AR方法
        Login::deleteAll(['uid' => $uid]);
    }

    /**
     * 批量删除User，无需构造实例.
     * @param array $uidList    要删除的用户id列表
     * @throws \Throwable       mysql错误，yii抛出
     */
    public static function batchDel( $uidList ){
        self::transaction(function()use( $uidList ){
            //删除所有零件
            self::delAllPart( $uidList );
            //删除用户本身
            static::deleteAll( ['uid' => $uidList] );
        });
    }

    /*
    * ========================================================
    * 零件定义关联
    * ========================================================
    * */

    /**
     * 资料零件的关联
     * @return \yii\db\ActiveQuery 仅返回查询器，不要返回结果。查询器可以串联使用。
     */
    public function getProfile(){
        return $this->hasOne(Profile::className(),['uid' => 'uid']);
    }

    /*
     * ========================================================
     * 工具相关
     * ========================================================
     * */

    /**
     * 将User实例转换为数组.
     * 先将User自身转换为数组，然后遍历各个零件，转换为数组类型嵌入到User上.
     * @param array | null $specify 仅允许的零件被转换为数组，null为全部零件
     * @return array
     */
    public function asArray( $specify = null ){
        //要转换的零件列表,此处定义的零件都在User上对应了getter,不定义会报错
        $partList = ['profile','loginBook'];
        //先转换自身
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