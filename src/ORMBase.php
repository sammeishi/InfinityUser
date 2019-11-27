<?php
namespace imsd\infinityUser;
use yii\db\ActiveRecord;
/*
 * ORM公用基类
 * 在ActiveRecord基础上，实现定制的ORM功能
 * @DIY缘由
 *  yii2的AR虽已具有ORM功能，以及ORM之间关联功能。
 *  但是：
 *  0：没有表前缀。
 *      只能在数据库连接上全局增加前缀。无法每个ORM独自增加
 *  1：缺少统一提交
 *      相互关联的ORM属性变更后，需要各自提交执行sql。
 * @提供功能
 *  0. 表前缀。通过派生类定义静态：$tbPrefix
 *  1. 统一提交。封装事务，传入回调，回调内的ORM操作最终都会被统一提交
 * */
class ORMBase extends ActiveRecord{
    /*
     * 继承yii的tableName，实现增加表前缀
     * 表前缀，必须是继承类静态常量：
     * 会自动向上查找继承类$tbPrefix静态常量作为前缀
     * 如果不定义$tbPrefix或者为空，则会放弃增加。
     * */
    public static $tbPrefix = "iu_";

    public static function tableName( $tb = null )
    {
        //如果继承类没有定义前缀，则跳过
        if( !isset( static::$tbPrefix ) || !static::$tbPrefix ){
            return $tb ? $tb : parent::tableName();
        }
        //如果指定传入表名直接处理
        if( $tb ){
            return self::$tbPrefix.$tb;
        }
        //无参数调用，向上查找继承类调用其tableName
        //父类tableName返回的是继承类类类名转换的表名
        //而且是模板格式，在模板中寻找入点插入表名
        $tb = parent::tableName();
        $mark = "{{%";
        $res = explode( $mark,$tb );
        return $mark.static::$tbPrefix.$res[1];
    }
    /*
     * 方便事务接口
     * 仅仅是封装yii2的ActiveRecord的事务
     * */
    public static function transaction( callable $fn){
        $transaction = ActiveRecord::getDb()->beginTransaction();
        try {
            $fn();
            $transaction->commit();
        } catch(\Exception $e) {
            throw $e;
        } catch(\Throwable $e) {
            throw $e;
        }
    }
}
?>