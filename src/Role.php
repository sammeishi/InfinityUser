<?php
namespace imsd\infinityUser;
/*
 * 角色类
 * */
class Role{
    /*
     * 角色属性
     * */
    public $rid; //角色id
    public $name; //角色名称
    public $permission = array(); //权限列表
    /*
     * 构造角色
     * */
    public function __construct( $init ){
        foreach ($init as $key => $val){
            if( $val && property_exists( $this,$key ) ){
                $this->{$key} = $val;
            }
        }
    }
}
?>