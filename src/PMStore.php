<?php
namespace imsd\infinityUser;
use imsd\infinityUser\ObjectStore;
use imsd\infinityUser\Permission;
/*
 * 权限存储
 * 存储，管理指定space的权限树
 * 用于界面配置角色时，选取权限。
 * @权限树
 *  权限本身是一个树结构，即A.children[] = B,C
 *  每个权限在树中被称为节点。
 * @ROOT根节点
 *  所有权限都从ROOT根节点开始，只有一个ROOT根节点，即使树是空的也存在
 * @路径path
 *  路径path是表达【一个节点/权限】在树中的位置，！！切记是一个节点/权限
 *  通过权限code链接起来表达。 如 X.Y.Z， 或者 X.Q.Z （这2个Z是不同的权限）
 * @匹配路径
 *  匹配【多个节点/权限】
 * @不能更新code
 *  暂时没实现
 *  因为父中，靠code链接子节点，更改code而不更改父中children，则会导致错乱
 *  可以find出来，更新name啥的。
 * @存储
 *  使用config类存储在config表中（序列化）
 * @数据结构
 *  Permission{
 *      code string 权限代码
 *      name string 权限名称
 *      children array 子权限
 *  }
 * @路径匹配
 *  匹配规则。可以匹配多个权限
 * */
class PMStore extends ObjectStore{
    protected $saveKey = "permission"; //config中存储名
    /*
     * 默认值为一个根节点
     * */
    protected static function def(){
        return Permission::ROOT();
    }
    /*
     * 查找指定权限
     * @param   string  $path   权限路径。如果为空返回根权限
     * @return  object  返回Permission实例
     * */
    public function find( $path = null,$ensureExist = false ){
        $curr = $this->data;
        foreach ( ( $path ?  explode(".",$path) : array() ) as $code ){
            $currChildren = &$curr->children;
            //处理当前节点不存在情况
            if( !isset( $currChildren[ $code ] ) ){
                //指明保证path存在，则创建不存在节点
                if( $ensureExist ){
                    $currChildren[ $code ] = new Permission( $code );
                }
            }
            //处理后当前节点仍然是空则停止
            if( !isset( $currChildren[ $code ] ) ){
                return null;
            }
            else{
                $curr = $currChildren[ $code ];
            }
        }
        return $curr;
    }
    /*
     * 在指定路径后面追加新权限
     * @保证路径存在
     *  如果指定路径中某个节点不存在，则会直接创建,保证路径一定存在。
     * @示例
     *  当前树 A->B
     *  append("A.B",new permission('C',"测试C"))
     *  最终： A->B->C
     *  append("A.B.X.Y",new permission('C',"测试C"))
     *  最终： A->B->X->Y->C. X和Y会被创建出来
     * */
    public function append( $path,$permissionInit ){
        $permission = new Permission( $permissionInit );
        $parent = $this->find( $path,true );
        if( $parent ){
            $parent->children[ $permission->code ] = $permission;
        }
        return $parent ? $permission : false;
    }
    /*
     * 删除权限
     * */
    public function remove( $path ){
        $codeList = explode(".",$path);
        $targetCode = array_pop($codeList);
        $parentPath = join( ".",$codeList );
        $parent = $this->find( $parentPath );
        if( $parent && isset( $parent->children[ $targetCode ] ) ){
            unset( $parent->children[ $targetCode ] );
        }
    }
    /*
     * 是否是空的
     * 只有一个根节点即为空
     * */
    public function isEmpty(){
        return count( $this->data->children ) === 0;
    }
    /*
     * 清空所有
     * */
    public function empty(){
        $this->data = self::def();
        $this->save();
    }
}
?>