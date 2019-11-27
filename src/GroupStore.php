<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ObjectStore;
use imsd\infinityUser\Group;
/*
 * 分组信息存储
 * 存储结构以gid为索引的一维列表
 * array(
 *     gid => new Group(gid,name),
 *     gid => new Group(gid,name),
 *     gid => new Group(gid,name),
 * )
 * */
class GroupStore extends ObjectStore{
    protected $saveKey = "group"; //config中存储名
    /*
     * 默认值为空数组
     * */
    protected static function def(){
        return array();
    }
    /*
     * 添加一个新的分组
     * */
    public function append( $groupInit ){
        !isset($groupInit['gid']) ? ($groupInit['gid'] = $this->makeId()) : null;
        $this->data[] = $group = new Group( $groupInit );
        return $group;
    }
    /*
     * 移除指定gid的分组
     * */
    public function remove( $gid ){
        if( $this->findById( $gid ) !== null ){
            unset($this->data[$gid]);
        }
    }
    /*
    * 基于当前list，生成一个组id
    * */
    public function makeId(){
        $id = 0;
        foreach ($this->data as $group){
            if( $group->gid > $id ){
                $id = $group->gid;
            }
        }
        return $id+1;
    }
    /*
     * 是否是空的
     * 只有一个根节点即为空
     * */
    public function isEmpty(){
        return count($this->data) === 0;
    }
    /*
     * 查找指定id的分组
     * 可批量查询
     * */
    public function findById( $gid ){
        return isset( $this->data[$gid] ) ? $this->data[$gid] : null;
    }
    /*
     * 查找子分组
     * */
    public function findChildren( $parentGid ){
        $res = array();
        foreach ($this->data as $group){
            if( $group->parentId === $parentGid ){
                $res[] = $group;
            }
        }
        return $res;
    }
    /*
     * 清空所有
     * */
    public function empty(){
        $this->data = Permission::ROOT();
        $this->save();
    }
}
?>