<?php
namespace imsd\infinityUser\common;
use yii\db\ActiveRecord;
/**
 * ORM公用基类.
 * 在ActiveRecord基础上扩展一些功能.<br>
 * 扩展功能.<br>
 *  - 表前缀。通过类静态成员：$tbPrefix. 自动加入前缀
 *  - 统一提交方法，传入回调，内所有操作都会被合并一个事务执行
 *  - 时间字段自动设置，构造时补全created_at 提交时补全updated_at
 * */
class ORMBase extends ActiveRecord{
    /**
     * 时间字段自动设置.
     * 当开启后，会在create/update时自动记录更新时间字段created_at/updated_at. <br>
     * 因此必须确保表拥有这2个字段,否则报错 <br>
     * @var bool 是否开启，默认开启
     */
    protected static $autoTimeFieldSet = true;

    /**
     * ORMBase构造.
     * 执行初始化数据检查.  <br>
     * 剔除不属于ORM表字段的属性 <br>
     * @param $init array   对象构造初始化数据
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($init = []){
        static::constructCheck( $init );
        parent::__construct( static::delUnknownProp( $init ) );
    }

    /**
     * 对构造实例的初始化数据进行检查.
     * 给继承类提供机会检查传入正确性。<br>
     * @param $init array 构造初始化数据
     */
    public static function constructCheck( $init ){
    }

    /**
     * 类初始化回调.
     * yii自动调用. <br/>
     * 如果开启时间字段自动设置，此处会增加created_at属性到类上
     * */
    public function init()
    {
        parent::init();
        //时间字段自动设置,只有new新实例才会被创建
        if( static::$autoTimeFieldSet && $this->getIsNewRecord() ){
            //自动追加创建时间
            !$this->created_at ? $this->created_at = date("Y-m-d H:i:s",time()) : null;
        }
    }

    /**
     * 将不属于本ORM表中字段的属性删除掉.
     * 用于提供特性：构造一个ORM时可以无脑传入参数，无用的被自动删除。
     * @param $props    array   要删除的属性集
     * @return array    返回删除后的属性集
     * @throws \yii\base\InvalidConfigException mysql执行相关异常
     */
    public static function delUnknownProp( $props ){
        $newProps = $props;
        if( is_array( $props ) ){
            $newProps = [];
            $fields = static::getTableSchema()->getColumnNames();
            foreach ( $props as $key => $val){
                if( in_array( $key,$fields ) ){
                    $newProps[ $key ] = $val;
                }
            }
        }
        return $newProps;
    }

    /**
     * 拦截yii保存之前事件.
     * 自动追加updated_at字段，用于记录更新时间
     * @param   bool    $insert yii参数，未使用
     * @return  bool    交回父类处理
     */
    public function beforeSave( $insert )
    {
        //自动设置 updated_at字段
        if( static::$autoTimeFieldSet ){
            if( count($this->getDirtyAttributes()) !== 0 ){
                $this->updated_at = date("Y-m-d H:i:s",time());
            }
        }
        //交回父类处理
        return parent::beforeSave($insert);
    }

    /**
     * 表前缀字符.
     * 此字符串在每次进行读写数据库时，加入到表名前. <br>
     * @var string 表前缀字符串
     */
    public static $tbPrefix = "iu_";

    /**
     * 重载yii,为本系统的表增加前缀：iu.
     * 此类是所有ORM类都会继承的，因此可以实现所有表加前缀<br>
     * 加前缀目的与其他系统/模块区分开<br>
     * yii只能在数据库链接上全局加前缀，所以会重载此方法自定义<br>
     * 也可以将本前缀为其他表加<br>
     * @param   null|string   $otherTable   为其他表增加前缀，而不是本ORM的表
     * @return  string|null                 返回加过前缀的表名
     */
    public static function tableName( $otherTable = null )
    {
        $prefix = (static::$tbPrefix) ? static::$tbPrefix : "";
        //其他表使用本项目的表前缀,直接返回
        if( $otherTable ){
            return $prefix.$otherTable;
        }
        //自身表加前缀
        else{
            $tb = parent::tableName();//从yii获取默认表名
            $mark = "{{%";
            $res = explode( $mark,$tb );
            return $mark.static::$tbPrefix.$res[1];
        }
    }

    /**
     * 统一事务接口.
     * 用于合并多个db操作，提高性能。
     * @param $fn   callable    回调方法
     * @throws \Throwable       事务失败,会自动回滚
     */
    public static function transaction( callable $fn){
        $transaction = ActiveRecord::getDb()->beginTransaction();
        try {
            $fn();
            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
?>