<?php
namespace imsd\infinityUser;
/*
 * 权限类
 * */
class Permission{
    public $code; //代码
    public $name; //名称
    public $des; //描述
    public $children = array(); //子权限
    /*
     * 构造
     * */
    public function __construct( $init = array() ){
        foreach ($init as $key => $val){
            if( $val && property_exists( $this,$key ) ){
                $this->{$key} = $val;
            }
        }
    }
    /*
     * 构造一个根节点
     * */
    public static function ROOT(){
        return new self( ['code'=>"ROOT",'name'=>'根权限'] );
    }
}
?>