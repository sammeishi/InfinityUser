<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ObjectStore;
use imsd\infinityUser\Role;
use imsd\infinityUser\Permission;
/*
 * 角色列表
 * 在config中，以array方式存储的角色实例列表
 * */
class RoleStore extends ObjectStore{
    protected $saveKey = "role";
    /*
     * 默认为数组
     * */
    protected static function def(){
        return [];
    }
    /*
     * 删除指定角色
     * */
    public function remove( $rid ){
        foreach( $this->data as $index => $role ){
            if( $role->rid === $rid ){
                array_splice( $this->data,$index,1 );
                return;
            }
        }
    }
    /*
     * 基于当前list，生成一个rid
     * */
    public function rid(){
        $end = count( $this->data ) - 1;
        $rid = $end === -1 ? 0 : $this->data[$end]->rid;
        return $rid + 1;
    }
    /*
     * 修改指定角色
     * */
    public function set( $targetRid,$newRole ){
        foreach( $this->data as $index => $role ){
            if( $role->rid === $targetRid ){
                foreach ( $newRole as $nkey => $nval ){
                    $role->{$nkey} = $nval;
                }
                $role->rid = $targetRid;
                return;
            }
        }
    }
    /*
     * 末尾追加新的角色
     * */
    public function append( $roleInit ){
        $role = new Role($roleInit);
        $role->rid ? null : $role->rid = $this->rid();
        $this->data[] = $role;
    }
    /*
     * 根据id获取角色信息
     * */
    public function getById( $rid ){
        return isset($this->data[$rid]) ? $this->data[$rid] : null;
    }
    /*
     * 获取所有
     * */
    public function all(){
        return $this->data;
    }
}
?>