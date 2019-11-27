<?php
namespace imsd\infinityUser;
use imsd\infinityUser\Config;
/*
 * 对象存储器
 * 将一个对象序列化之后存储在配置中
 * 取出时会反序列化
 * @按空间划分
 * */
class ObjectStore{
    private $space = null; //所属空间
    protected $saveKey = null; //在配置中的保存名
    protected $data = null; //数据,反序列化后的
    /*
     * 构造一个指定space的权限树
     * */
    public function __construct( $space,$saveKey = null ){
        $this->space = $space;
        $saveKey ? $this->saveKey = $saveKey : null;
        $this->load();
    }
    /*
     * 获取保存的key，前缀object_store
     * */
    public function getSaveKey(){
        return "object_store_".$this->saveKey;
    }
    /*
     * 从存储中加载数据
     * 会覆盖原先的data数据！！
     * */
    private function load(){
        $object = Config::get(
            $this->space,
            $this->getSaveKey(),
            Config::$VAL_SAVE_TYPE_SERIALIZE
        );
        if( !$object ){
            $object = static::def();
        }
        return $this->data = $object;
    }
    /*
     * 加载默认值
     * 如果加载后为空或者null，则使用此返回值
     * */
    protected static function def(){
        return null;
    }
    /*
     * 保存数据
     * 将整个权限树序列化之后使用config存储
     * */
    public function save(){
        Config::set(
            $this->space,
            $this->getSaveKey(),
            $this->data,
            Config::$VAL_SAVE_TYPE_SERIALIZE
        );
    }
    /*
     * 获取数据
     * */
    public function all(){
        return $this->data ? $this->data : static::def();
    }
}
?>