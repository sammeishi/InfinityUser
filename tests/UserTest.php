<?php
declare(strict_types=1);
require_once "start.yii.php";
require_once  dirname( __DIR__ ). "/src/User.php";
use PHPUnit\Framework\TestCase;
use imsd\infinityUser\User;

/**
 * 测试用户类.
 */
final class UserTest extends TestCase{
    /**
     * 获取一个用户构造数据
     */
    private function makeUserData(){
        return $data = [
            'space' => "def",
            'group_id' => rand(1,100000),
            'role_id' => rand(1,100000),
            "profile" =>[
                "name" => $this->randomStr( 10 ),
                "gender" => rand(0,1),
                "contact" => rand(1,11),
                "birthday" => date("Y-m-d",time()),
            ]
        ];
    }

    /**
     * 插入一个随机用户
     */
    private function addUser(){
        //测试插入
        $userData = $this->makeUserData();
        $user = new User( $userData );
        $user->save();
        $this->assertIsObject( $user );
        $this->assertIsInt( $user->uid );
        return $user;
    }

    /**
     * 测试添加，修改，查询，删除
     */
    public function test_User_RW(){
        //测试插入
        $user = $this->addUser();
        $uid = $user->uid;
        //测试查出
        $findUser = User::findOne(['uid'=>$user->uid]);
        $this->assertNotNull( $findUser );
        $this->assertEquals( $user->asArray(),$findUser->asArray() );
        //测试修改
        $groupId = rand(100,10000);
        $roleId = rand(100,10000);
        $space = "new_space";
        $user->space = $space;
        $user->group_id = $groupId;
        $user->role_id = $roleId;
        $user->save();
        $findUser = User::findOne(['uid'=>$user->uid]);
        $this->assertIsObject( $findUser );
        $this->assertEquals( $findUser->space,$space );
        $this->assertEquals( $findUser->group_id,$groupId );
        $this->assertEquals( $findUser->role_id,$roleId );
        //删除
        $user->delete();
        //检查删除情况
        $this->assertEmpty( User::findOne(['uid'=>$uid]) );
    }

    /**
     * 测试登录薄.
     * 插入登录薄，检测插入，密码验证,删除登录薄
     */
    public function test_LoginBook_RW(){
        //测试插入
        $user = $this->addUser();
        $uid = $user->uid;
        //登录薄数据
        $lgData = [];
        $n = 1;
        while ( $n-- ){
            $lgData[] = [
                'platform' => $this->randomStr(10),
                'account'=>$this->randomStr(15),
                'pwd'=>$this->randomStr(15),
            ];
        }
        $loginBook = User::createLoginBook($lgData);
        $user->replaceLoginBook( $loginBook );
        //读出数量对比
        $findLoginBook = $user->getLoginBook()->all();
        $this->assertEquals( count( $loginBook ),count( $findLoginBook ) );
        //将loginBook逐条去验证.
        foreach ( $lgData as $loginData ){
            $this->assertTrue(  $user->verifyLogin( $loginData['platform'],$loginData['account'],$loginData['pwd'] ) );
        }
        //删除
        $user->emptyLoginBook();
        //检查删除
        $this->assertEmpty( $user->getLoginBook()->all() );
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