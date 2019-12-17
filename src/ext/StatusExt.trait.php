<?php
namespace imsd\infinityUser\ext;

/**
 * 状态相关的扩展.
 * Trait StatusExt
 * @package imsd\infinityUser\ext
 */
trait StatusExt{

    /**
     * 批量禁用用户.
     * 仅仅是将状态设置为禁用状态.
     * @param array $uidList    要禁用的uid列表
     */
    public static function batchDisable( $uidList ){
        static::updateAll(
            ['status' => static::$STATUS_DISABLE],
            ['uid' => $uidList]
        );
    }

    /**
     * 实例上的禁用方法.
     * 仅仅是将状态设置为禁用状态.
     */
    public function disable(){
        $this->status = static::$STATUS_DISABLE;
        $this->save();
    }
}
?>