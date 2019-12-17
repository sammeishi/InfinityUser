<?php
namespace imsd\infinityUser\ext;
use imsd\infinityUser\part\Login;
use imsd\infinityUser\part\Profile;

/**
 * 零件相关扩展功能.
 *  - 所有零件创建方法
 *  - 所有零件销毁方法
 *  - 零件定义列表
 */
trait PartExt{

    /**
     * 零件列表定义.
     * 如果有自己的零件，请重载！
     * @return array    返回所有零件定义
     */
    public static function partDefine(){
        return [
            'profile' => Profile::class,
            'login' => Login::class,
        ];
    }

    /**
     * 创建所有零件.
     * 零件必须是ORM继承类<br>
     *  因为使用yii的关联特性进行同时创建插入表. <br>
     * 仅仅返回实例<br>
     *  无需保存插入，只需实例，User会自动link插入<br>
     * 不会全部生成<br>
     *  只有传入对应零件的init数据才会生成
     * @param   array   $allInit    User完整初始化数据，也包含各个零件的数据
     * @return  array               返回零件对象列表
     */
    public static function createAllPart( $allInit ){
        $list = array();
        //构造每个零件的实例
        foreach ( static::partDefine() as $name => $partClass){
            $init = isset( $allInit[$name] ) ? $allInit[ $name ] : null;
            $init ? ($list[ $name ] = new $partClass( $init )) : null;
        }
        //返回所有零件
        return $list;
    }

    /**
     * 删除一个或者多个用户的所有零件.
     * 真正的从数据库中删除数据. <br>
     * 零件都是基于Yii的AR，直接调用其deleteAll <br>
     * deleteAll来自yii的AR
     * @param int | array   $uid    用户id,也可以传入数组批量删除
     */
    public static function destroyAllPart( $uid ){
        self::transaction( function(  ) use ( $uid ) {
            foreach ( static::partDefine() as $partClass ){
                $partClass::deleteAll( ['uid' => $uid] );
            }
        } );
    }

    /*
    * ========================================================
    * 零件在ORM上，与User类的关联.
     * 关联后，可通过userIns->partName来获取零件对象！
     * 【无法关联单个login】
     *  login必须指定平台帐号才能去查询，yii的默认关联，需要无参数!
    * ========================================================
    * */

    /**
     * 资料零件的关联
     * @return \yii\db\ActiveQuery 仅返回查询器，不要返回结果。查询器可以串联使用。
     */
    public function getProfile(){
        return $this->hasOne(Profile::className(),['uid' => 'uid']);
    }
}
?>