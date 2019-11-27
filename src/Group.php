<?php
namespace imsd\infinityUser;
/*
 * 用户分组
 * */
class Group{
    /*
     * 角色属性
     * */
    public $gid; //分组id
    public $name; //分组名称
    public $parentId = null;//所属父分组id
    /*
     * 构造角色
     * */
    public function __construct( $init = array() ){
        foreach ($init as $key => $val){
            if( $val && property_exists( $this,$key ) ){
                $this->{$key} = $val;
            }
        }
    }
    /*
     * 创建一个子分组
     * */
    public function createChild( $groupInit ){
        $groupInit->parentId = $this->gid;
        return new self( $groupInit );
    }
}
?>