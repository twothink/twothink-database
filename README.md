# twothink-database
数据库备份还原优化扩展
### 使用composer进行安装
~~~
     composer require twothink/twothink-database dev-master
~~~
### 使用composer update进行安装
~~~
    "require": {
        "twothink/twothink-database": "dev-master"
    },
~~~
### 引入类文件
~~~
use twothink\Database;
//facade模式
use twothink\facade\Database; 
~~~
### 文件命名规则，请严格遵守（温馨提示）
~~~
$file=['name'=>date('Ymd-His'),'part'=>1]
~~~
### 获取数据库表列表
~~~
$list  = Database::getDataList();
~~~
### 优化表
~~~
$table = ["twothink_action","twothink_action_log","twothink_addons"];
//$table = "twothink_action";
$list  = Database::optimize($table);
~~~
### 修复表
~~~
$table = ["twothink_action","twothink_action_log","twothink_addons"];
//$table = "twothink_action";
$list  = Database::repair($table);
~~~
### 备份表配置
~~~
//备份配置
$config = [
            'path' => realpath('./data') . DIRECTORY_SEPARATOR
            ,'part' => 20971520
            ,'compress' => 0
            ,'level' => 9
        ];
//生成备份文件信息
$file = ['name' => date('Ymd-His', time()), 'part' => 1];
Database::setAttr(['config'=>$config,'file'=>$file]);
~~~
### 备份表初始化
~~~
//备份配置
$config = [
            'path' => realpath('./data') . DIRECTORY_SEPARATOR
            ,'part' => 20971520
            ,'compress' => 0
            ,'level' => 9
        ];
//生成备份文件信息
$file = ['name' => date('Ymd-His', time()), 'part' => 1];
Database::Backup::setAttr(['config'=>$config,'file'=>$file])->backupinit($tables);//备份初始化检测
Database::create();//创建备份文件
~~~
### 备份表
~~~
Database::setAttr(['config'=>$config,'file'=>$file])->backup($table, 0);
~~~
### 备份数据列表
~~~
Database::fileList($path);
~~~
### 删除备份文件
~~~
Database::delFile($time,$path=false)
~~~
### 下载备份文件
~~~
Database::downloadFile($time);
~~~
### 还原导入
~~~
Database::import($start)
~~~


