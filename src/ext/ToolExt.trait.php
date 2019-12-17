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
?>