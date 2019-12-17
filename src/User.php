<?php
namespace imsd\infinityUser;
require __DIR__ . "/ext/LoginExt.trait.php";
require __DIR__ . "/ext/DelExt.trait.php";
require __DIR__ . "/ext/PartExt.trait.php";
require __DIR__ . "/ext/StatusExt.trait.php";
require __DIR__ . "/ext/ToolExt.trait.php";
use imsd\infinityUser\common\ORMBase;
use imsd\infinityUser\ext\LoginExt;
use imsd\infinityUser\ext\DelExt;
use imsd\infinityUser\ext\PartExt;
use imsd\infinityUser\ext\StatusExt;
use imsd\infinityUser\ext\ToolExt;
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
 * 将用户功能都分割出来，包括表。这些分割的对象就是零件<br>
 * - 零件必须对应一个表
 * - 零件与User必须靠uid关联
 *   - 写死的，请遵循！
 * - 零件必须被定义,即partDefine方法<br>
 *   - 如果不定义，用户实例被销毁时零件无法被销毁
 * - 一对多的零件不要关联
 *   - 一对一的零件关联是很完美的，因为不需要查询参数。如User->profile
 *      但是一对多不行，如User->login会返回全部login，但仅仅只需其中一两个！
 *
 * 注意：先保存才能link. <br>
 * ActiveRecord的禁止2个未保存ORM进行link <br>
 * 如果new user之后没有save，零件link这个user，会报错！<br>
 * 因为没有ID进行join,请确保user立即save之后在link！<br>
 *
 * 删除操作. <br>
 * 用户的数据可能会被其他模块使用，如优惠券，消费记录等，贸然从数据库删除会导致其他数据异常 <br>
 * 因此关于删除提供了如下操作.
 *  - 删除 delete     仅标记为删除状态，不参与任何业务，但是数据存在
 *  - 销毁 destroy    真真正正从数据库中删除掉
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
     * 此状态必须转换为正常才可使用。
     * @var int
     */
    public static $STATUS_INTENDED = -999998;
    /**
     * 用户状态：被删除
     * 但是因为需要数据因此保留了。
     * 此状态下，用户等同于不存在
     */
    public static $STATUS_DELETED = -999999;

    /**
     * 加载登录扩展
     */
    use LoginExt;

    /**
     * 加载删除操作扩展.
     * 包含监听删除事件，删除零件
     */
    use DelExt;

    /**
     * 加载零件扩展.
     *  - 创建所有零件方法
     *  - 销毁所有零件方法
     *  - 各个零件定义列表
     */
    use PartExt;

    /**
     * 加载状态相关的扩展.
     *  - 禁用/启用
     */
    use StatusExt;

    /**
     * 加载工具方法扩展。
     */
    use ToolExt;

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
        $user->status = self::$STATUS_INTENDED;
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
}