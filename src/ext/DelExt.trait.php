<?php
namespace imsd\infinityUser\ext;
use imsd\infinityUser\part\Login;
use imsd\infinityUser\part\Profile;

/**
 * 删除相关操作的扩展.
 * 零件也要删除. <br>
 * 当对一个User做删除操作时，仅仅是User对象，它包含的零件也要删除。
 * 通过继承delAllPart，来实现零件的删除.
 * User类本身实现了默认的一些零件删除.
 * 关于删除的操作：<br>
 * 用户的数据可能会被其他模块使用，如优惠券，消费记录等，贸然从数据库删除会导致其他数据异常 <br>
 * 因此关于删除提供了如下操作.
 *  - 删除 delete     仅标记为删除状态，不参与任何业务，但是数据存在
 *  - 销毁 destroy    真真正正从数据库中删除掉
 */
trait DelExt{

    /**
     * 拦截yii事件实例删除之前,清空零件数据.
     * User删除时候会触发，但是只能删除User自身无法删除零件.<br>
     * 因此在此事件回调中删除各个零件 <br>
     * 此回调执行删除零件，也会被计入事务内，因为yii的delete已经开启了事务
     * @return bool
     */
    public function beforeDelete(){
        //删除所有零件
        self::destroyAllPart( $this->uid );
        //必须返回true，否则会终止删除操作。
        return true;
    }

    /**
     * 删除User.
     * 仅仅是标记为删除状态，并不会真正从数据库移除!
     */
    public function del(){
        $this->status = static::$STATUS_DELETED;
        $this->save();
    }

    /**
     * 批量删除User.
     * 仅仅是标记状态为删除，数据还在.
     * @param array $uidList    uid列表
     */
    public function batchDel( $uidList ){
        static::updateAll([
            'status' => static::$STATUS_DELETED
        ],[
            'uid' => $uidList
        ]);
    }

    /**
     * 销毁user.
     * 真正的从数据库中移除. <br>
     * 同时会删除零件
     */
    public function destroy(){
        $user = $this;
        self::transaction(function()use( $user ){
            //yii接口上的del
            $user->delete();
            //删除零件
            $user::destroyAllPart( $user->uid );
        });
    }

    /**
     * 批量删除User，无需构造实例.
     * @param array $uidList    要删除的用户id列表
     * @throws \Throwable       mysql错误，yii抛出
     */
    public static function batchDestroy( $uidList ){
        self::transaction(function()use( $uidList ){
            //删除所有零件
            static::destroyAllPart( $uidList );
            //删除用户本身
            static::deleteAll( ['uid' => $uidList] );
        });
    }
}
?>