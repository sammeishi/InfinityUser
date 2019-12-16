<?php
declare(strict_types=1);
require_once "start.yii.php";
require_once  dirname( __DIR__ ). "/src/common/Config.php";
use PHPUnit\Framework\TestCase;
use imsd\infinityUser\common\Config;

/*
 * 测试对象
 */
class TestObj{
    public $content;
    public $array = null;
    public $int = 10345;
    public $str = null;
    public function __construct( $a ){
        $this->content = $a;
        $this->array = [ 1,2,3,4,5 ];
        $this->str = "qsfj13j4efj1324jfjqwdf";
    }
}

final class ConfigTest extends TestCase{

    /*
     * 测试默认空间下读写
     */
    public function test_default_config_RW(){
        $config = new Config();
        $varList = [
            rand( 10, 1000000 ), //int
            $this->randomStr( 30 ), //str
            $this->randomStr( 30000 ), //long str
            new TestObj( rand( 10, 1000000 ) ) , //obj
        ];
        try{
            foreach ( $varList as $index => $var ){
                $key = "test_default_space_RW_".$index;
                //写入测试
                $config->set( $key,$var );
                //读出测试
                $this->assertEquals( $var,$config->get( $key ) );
                //删除测试
                $config->del( $key );
                $this->assertNull( $config->get( $key ) );
            }
        }
        catch ( \Exception $e){
            $this->assertTrue( false,$e->getMessage() );
        }
    }
    /*
     * 随机字符串
     */
    public function randomStr( $n ){
        $pattern = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ";
        $length = strlen( $pattern );
        $str = "";
        for( $i=0; $i<$n; $i++ ) {
            $str .= $pattern{mt_rand(0,$length -1 )};
        }
        return $str;
    }

}