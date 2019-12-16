#### 全能用户模块 InfinityUser
1. 可扩展的架构
2. 集中管理用户，通过空间区分
3. 增删改与管理功能

### 依赖
0. php7.1
1. yii2
2. DefDev开发虚拟机
3. phpunit
4. phpdoc

#### phpdoc
所有API接口都在phpdoc中。phpdoc使用composer安装，根目录phpdoc.sh直接运行即可生成API文档。

#### 单元测试
使用phpunit单元测试，测试用例在tests目录下，可直接在linux下运行phpunit.sh

### 仅支持linux开发
所有，包含测试，doc生成都跑在centos下。win不支持！
