<?php
namespace imsd\infinityUser\ext;
use yii\helpers\ArrayHelper;

/**
 * 工具相关的扩展.
 * Trait StatusExt
 * @package imsd\infinityUser\ext
 */
trait ToolExt{
    /**
     * 将User实例转换为数组.
     * 默认包含零件,可以追加指定零件
     * @param array | null $specify 追加的零件
     * @return array
     */
    public function asArray( $specify = null ){
        $specify = ($specify && !is_array( $specify )) ?  [ $specify ] : $specify;
        $specify = $specify ? $specify : [];
        //此处定义的零件都在User上对应了getter,不定义会报错
        $partList = array_merge( ['profile']  ,$specify );
        //先转换自身
        $res = ArrayHelper::toArray($this);
        //遍历获取零件，会触发sql查询！然后转换为Array
        foreach ( $partList as $partName ){
            $res[ $partName ] = ArrayHelper::toArray($this->{$partName});
        }
        return $res;
    }
}
?>