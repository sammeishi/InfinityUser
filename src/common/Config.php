<?php
namespace imsd\infinityUser\common;

/**
 * 配置项管理类,读写指定空间下的配置数据.
 * 配置是KV结构，同空间下，key必须是唯一的，不可重复. <br>
 * 为了保持值的类型，将会对值进行序列化,省事
 * */
class Config{
    /**
     * 存储表名
     * @val string
     */
    private static $table = "iu_config";

    /**
     * 配置所处的空间下.
     * 如果不传入默认为公共空间
     * @val string
     */
    private $space = "#public#";

    /**
     * 构造.
     * 指明管理的空间,如果不指明，会被使用全局空间
     * @param string    $space  本配置管理所处的空间
     */
    public function __construct(  $space = null ){
        $space ? $this->space = $space : null;
    }

    /**
     * 更新一个配置项.
     * 如果不存在配置记录，会插入.如果存在则更新. <br>
     * 会对值保持类型。直接序列化！
     * @param string $key 此配置项的键
     * @param mixed $val 配置值
     * @return  void
     * @throws \yii\db\Exception    mysql执行异常
     */
    public function set( $key, $val ) {
        //存在，更新
        if( self::isExist( $key,$this->space ) ){
            \Yii::$app->db->createCommand()->update(
                self::$table,
                ['val' => self::serialize( $val )],
                [ 'key' => $key,'space' => $this->space ]
            )->execute();
        }
        //不存在，插入
        else{
            \Yii::$app->db->createCommand()->insert(self::$table, [
                'space' => $this->space,
                'key' => $key,
                'val' => self::serialize( $val ),
            ])->execute();
        }
    }

    /**
     * 读取一个配置项的值.
     * 将会对值进行反序列化
     * @param   string          $key    此配置项的键
     * @return mixed                    查询不到返回null
     */
    public function get( $key ){
        $res = (new \yii\db\Query())->select(['val'])
            ->from(self::$table)
            ->where([ 'space' => $this->space, 'key' => $key])
            ->one();
        return $res ? self::unserialize( \current( $res ) ) : null;
    }

    /**
     * 序列化值为字符串用于存储
     * @param mixed 要序列化的值
     * @return string   返回序列化后字符串
     */
    public static function serialize( $val ){
        return serialize( $val );
    }

    /**
     * 反序列化.
     * 从字符串解析出数据
     * @param string    $valStr 序列化后的字符串
     * @return mixed            还原出的数据
     */
    public static function unserialize( $valStr ){
        return \unserialize( $valStr );
    }

    /**
     * 删除一个配置.
     * @param string  $key  要删除的key名
     * @throws \yii\db\Exception    mysql执行错误
     */
    public function del(  $key ) {
        \Yii::$app->db->createCommand()->delete(
            self::$table,
            ['key' => $key, 'space' => $this->space ]
        )->execute();
    }

    /**
     * 判定一个空间下，是否存在key.
     * @param string $key 要查询的key
     * @param string $space key所在的space
     * @return bool
     */
    private static function isExist(  $key, $space ){
        return (new \yii\db\Query())
                ->select(['count(1)'])
                ->where(['space'=>$space,'key' => $key])
                ->from(self::$table)
                ->count() != 0;
    }
}
?>