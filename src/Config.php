<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ORMBase;
use yii\db\ActiveRecord;
use yii\db\Exception;

/*
 * 配置项
 * 每一个config实例代表表中的一行
 * @单一配置
 *  key是唯一性的，不可重复的被称为单一配置项
 * @组配置
 *  key相同的配置，被称为组配置，通过sort进行排序
 * */
class Config extends ORMBase {
    public static $VAL_SAVE_TYPE_NONE = null; //原始模式,不做任何处理
    public static $VAL_SAVE_TYPE_JSON = 1; //值存储类型：json
    public static $VAL_SAVE_TYPE_JSON_ARRAY = 2; //值存储类型：json存储的数组。
    public static $VAL_SAVE_TYPE_SERIALIZE = 3; //值存储类型：序列化对象
    /*
     * 快速查询出值
     * @可对值解码
     *  如果存储的值事先编码，此处可指定对应解码 666!
     * */
    public static function get( $space,$key,$valType = null ){
        $row = self::findOne(['space'=>$space,'key'=>$key]);
        if( $row ){
            return self::convertSaveVal('decode',$valType,$row->val);
        }
        else{
            return null;
        }
    }
    /*
     * 快速设置一个配置项
     * 如果存在更新
     * 如果不存在则插入
     * @可对值编码
     *  可对值进行支持的编码后，在存储。
     * */
    public static function set( $space,$key,$val,$valType = null ){
        $row = new self();
        $row->space = $space;
        $row->key = $key;
        $row->val = self::convertSaveVal( "encode",$valType,$val );
        $row->apply();
    }
    /*
     * 转换配置的值，用于存储
     *
     * */
    public static function convertSaveVal( $action, $type,$val ){
        //编码
        if( $action === "encode" ){
            switch ($type){
                case self::$VAL_SAVE_TYPE_JSON:{
                    $val = json_encode( $val );
                    break;
                }
                case self::$VAL_SAVE_TYPE_SERIALIZE:{
                    $val = serialize( $val );
                    break;
                }
            }
            return $val;
        }
        //解码
        else if( $action === "decode" ){
            switch ($type){
                case self::$VAL_SAVE_TYPE_JSON:{
                    $val = json_decode( $val );
                    break;
                }
                case self::$VAL_SAVE_TYPE_JSON_ARRAY:{
                    $val = json_decode( $val ,true );
                    break;
                }
                case self::$VAL_SAVE_TYPE_SERIALIZE:{
                    try{
                        $val = unserialize( $val );
                    }
                    catch (\Exception $e){
                        $val = null;
                    }
                    break;
                }
            }
            return $val;
        }
    }
    /*
     * 应用配置的更改
     * 会检查是否存在，不存在则重新插入，存在则更改
     * @组配置勿用！！
     *  key值一样的组配置，请勿使用此API! 因为key并不是唯一，会一直误判重复
     * @存在判断
     *  space + key
     * */
    public function apply(){
        //查找已存在的
        $exist = self::findOne( ['space'=>$this->space,'key'=>$this->key] );
        //存在直接将藏属性更新进去
        if( $exist ){
            foreach ( $this->getDirtyAttributes() as $key =>$val ){
                $exist->{$key} = $val;
            }
            $exist->save();
        }
        //否则保存
        else{
            $this->save();
        }
    }
}
?>