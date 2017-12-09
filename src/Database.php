<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 苹果  593657688@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace twothink;

use think\Exception;
/*
 * 数据导出模型
 * @Author: 苹果  <593657688@qq.com>
 */
class Database{
    /**
     * 文件指针
     * @var resource
     */
    private $fp;

    /**
     * 备份文件信息 part - 卷号，name - 文件名
     * @var array
     */
    private $file;

    /**
     * 当前打开文件大小
     * @var integer
     */
    private $size = 0;

    /**
     * 备份配置
     * @var integer
     */
    private $config=[
        'path' => './data/',
        //数据库备份路径
        'part' => 20971520,
        //数据库备份卷大小
        'compress' => 0,
        //数据库备份文件是否启用压缩 0不压缩 1 压缩
        'level' => 9,
    ];
    /*
     * 数据库连接
     */
    public static function connect()
    {
        return db();
    }
    /*
     * 数据库表列表
     * @param string $table 表名
     * @param int    $type  查询类型
     */
    public function getDataList($table = null,$type=1)
    {
        $db = self::connect();
        if (is_null($table)) {
            $list = db()->query("SHOW TABLE STATUS");
        } else {
            if ($type) {
                $list = $db->query("SHOW FULL COLUMNS FROM {$table}");
            }else{
                $list = $db->query("show columns from {$table}");
            }
        }
        return array_map('array_change_key_case', $list);
    }
    /**
     * 优化表
     * @param  String $table 表名
     * @return String $table
     */
    public function optimize($table = null)
    {
        if ($table) {
            $db = self::connect();
            if (is_array($table)) {
                $table = implode('`,`', $table);
                $list = $db->query("OPTIMIZE TABLE `{$table}`");
            } else {
                $list = $db->query("OPTIMIZE TABLE `{$table}`");
            }
            if ($list) {
                return $table;
            } else {
                throw new Exception("数据表'{$table}'优化出错请重试！");
            }
        } else {
            throw new Exception("请指定要优化的表！");
        }
    }
    /**
     * 修复表
     * @param  String $table 表名
     * @return String $table
     */
    public function repair($table = null)
    {
        if ($table) {
            $db = self::connect();
            if (is_array($table)) {
                $table = implode('`,`', $table);
                $list = $db->query("REPAIR TABLE `{$table}`");
            } else {
                $list = $db->query("REPAIR TABLE `{$table}`");
            }
            if ($list) {
                return $list;
            } else {
                throw new \Exception("数据表'{$table}'修复出错请重试！");
            }
        } else {
            throw new \Exception("请指定要修复的表！");
        }
    }

    /**
     * 检查目录是否可写
     * @param  string   $path    目录
     * @return bool
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }
        if (mkdir($path, 0755, true)) {
            return true;
        } else {
            return false;
        }
    }
    /*
     * 删除备份文件
     * @param string $path 目录
     */
    public function delFile($time,$path=false)
    {
        if ($time) {
            if($path){
                $this->config['path'] = $path;
            }
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath($this->config['path']) . DIRECTORY_SEPARATOR . $name;
            array_map("unlink", glob($path));
            if (count(glob($path))) {
                throw new Exception('备份文件删除失败，请检查权限！');
            }
            return true;
        } else {
            throw new Exception("{$time} Time参数错误！");
        }
    }
    /**
     * 下载备份
     * @param string $time
     * @param integer $part
     * @return array|mixed|string
     */
    public function downloadFile($time, $part = 0)
    {
        $file = $this->getFile('time', $time);
        $fileName = $file[$part];
        if (file_exists($fileName)) {
            ob_end_clean();
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . filesize($fileName));
            header('Content-Disposition: attachment; filename=' . basename($fileName));
            readfile($fileName);
        } else {
            throw new Exception("{$time} File is abnormal");
        }
    }
    public function getFile($type = '', $time = 0)
    {
        //
        if (!is_numeric($time)) {
            throw new Exception("{$time} Illegal data type");
        }
        switch ($type) {
            case 'time':
                $name = date('Ymd-His', $time) . '-*.sql*';
                $path = realpath($this->config['path']) . DIRECTORY_SEPARATOR . $name;
                return glob($path);
                break;
            case 'timeverif':
                $name = date('Ymd-His', $time) . '-*.sql*';
                $path = realpath($this->config['path']) . DIRECTORY_SEPARATOR . $name;
                $files = glob($path);
                $list = array();
                foreach ($files as $name) {
                    $basename = basename($name);
                    $match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                    $gz = preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql.gz$/', $basename);
                    $list[$match[6]] = array($match[6], $name, $gz);
                }
                $last = end($list);
                if (count($list) === $last[0]) {
                    return $list;
                } else {
                    throw new Exception("File {$files['0']} may be damaged, please check again");
                }
                break;
            case 'pathname':
                return "{$this->config['path']}{$this->file['name']}-{$this->file['part']}.sql";
                break;
            case 'filename':
                return "{$this->file['name']}-{$this->file['part']}.sql";
                break;
            case 'filepath':
                return $this->config['path'];
                break;
            default:
                $arr = array('pathname' => "{$this->config['path']}{$this->file['name']}-{$this->file['part']}.sql", 'filename' => "{$this->file['name']}-{$this->file['part']}.sql", 'filepath' => $this->config['path'], 'file' => $this->file);
                return $arr;
        }
    }
    /*
     * 数据库备份文件列表
     * @param string $path 目录
     */
    public function fileList($path=false)
    {
        if($path){
            $this->config['path'] = $path;
        }
        if (!is_dir($this->config['path'])) {
            mkdir($this->config['path'], 0755, true);
        }
        $path = realpath($this->config['path']);
        $flag = \FilesystemIterator::KEY_AS_FILENAME;
        $glob = new \FilesystemIterator($path, $flag);
        $list = array();
        foreach ($glob as $name => $file) {
            if (preg_match('/^\\d{8,8}-\\d{6,6}-\\d+\\.sql(?:\\.gz)?$/', $name)) {
                $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
                $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                $part = $name[6];
                if (isset($list["{$date} {$time}"])) {
                    $info = $list["{$date} {$time}"];
                    $info['part'] = max($info['part'], $part);
                    $info['size'] = $info['size'] + $file->getSize();
                } else {
                    $info['part'] = $part;
                    $info['size'] = $file->getSize();
                }
                $extension = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                $info['compress'] = $extension === 'SQL' ? '-' : $extension;
                $info['time'] = strtotime("{$date} {$time}");
                $list["{$date} {$time}"] = $info;
            }
        }
        return $list;
    }
    /*
     * 备份数据库初始化检测
     */
    public function backupinit(){
        //检查文件是否可写
        if (!$this->checkPath($this->config['path'])) {
            throw new Exception("当前目录不可写!");
        }
        //检查是否有正在执行的任务
        $lock = "{$this->config['path']}backup.lock";
        if (is_file($lock)) {
            throw new Exception('检测到有一个备份任务正在执行，请稍后再试！');
        } else {
            //创建锁文件
            file_put_contents($lock, time());
        }
        //检查备份目录是否可写
        if (!is_writeable($this->config['path'])) {
            throw new Exception('备份目录不存在或不可写，请检查后重试！');
        }
        return $this;
    }
    /**
     * 打开一个卷，用于写入数据
     * @param  integer $size 写入数据的大小
     */
    private function open($size){
        if($this->fp){
            $this->size += $size;
            if($this->size > $this->config['part']){
                $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                session('backup_file', $this->file);
                $this->create();
            }
        } else {
            $backuppath = $this->config['path'];
            $filename   = "{$backuppath}{$this->file['name']}-{$this->file['part']}.sql";
            if($this->config['compress']){
                $filename = "{$filename}.gz";
                $this->fp = @gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = @fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }

    /**
     * 写入初始数据
     * @return boolean true - 写入成功，false - 写入失败
     */
    public function create(){
        $sql  = "-- -----------------------------\n";
        $sql .= "-- SentCMS MySQL Data Transfer \n";
        $sql .= "-- \n";
        $sql .= "-- Host     : " . config('database.hostname') . "\n";
        $sql .= "-- Port     : " . config('database.hostport') . "\n";
        $sql .= "-- Database : " . config('database.database') . "\n";
        $sql .= "-- \n";
        $sql .= "-- Part : #{$this->file['part']}\n";
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- -----------------------------\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }

    /**
     * 写入SQL语句
     * @param  string $sql 要写入的SQL语句
     * @return boolean     true - 写入成功，false - 写入失败！
     */
    private function write($sql){
        $size = strlen($sql);

        //由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
        //一般情况压缩率都会高于50%；
        $size = $this->config['compress'] ? $size / 2 : $size;

        $this->open($size);
        return $this->config['compress'] ? @gzwrite($this->fp, $sql) : @fwrite($this->fp, $sql);
    }

    /**
     * 备份表结构
     * @param  string  $table 表名
     * @param  integer $start 起始行数
     * @return boolean        false - 备份失败
     */
    public function backup($table, $start){
        //创建DB对象
        $db = self::connect();

        //备份表结构
        if(0 == $start){
            $result = $db->query("SHOW CREATE TABLE `{$table}`");
            $sql  = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= trim($result[0]['Create Table']) . ";\n\n";
            if(false === $this->write($sql)){
                return false;
            }
        }

        //数据总数
        $result = $db->query("SELECT COUNT(*) AS count FROM `{$table}`");
        $count  = $result['0']['count'];

        //备份表数据
        if($count){
            //写入数据注释
            if(0 == $start){
                $sql  = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }

            //备份数据记录
            $result = $db->query("SELECT * FROM `{$table}` LIMIT {$start}, 1000");

            foreach ($result as $row) {
//                $row = array_map('addslashes', $row);
//                $sql = "INSERT INTO `{$table}` VALUES ('" . str_replace(array("\r","\n"),array('\\r','\\n'),implode("', '", $row)) . "');\n";

                $sql="INSERT INTO `{$table}` VALUES";
                $sql.='(';
                foreach ($row as $v2){
                    if ($v2===null) {
                        $sql.="NULL,";
                    }else{
                        $v2 = str_replace(["\r","\n"],['\r','\n'],addslashes($v2));
                        $sql.="'$v2',";
                    }
                }
                $sql=mb_substr($sql, 0, -1);
                $sql.="),\r\n";
                $sql=mb_substr($sql, 0, -3);
                $sql.=";\n";

                if(false === $this->write($sql)){
                    return false;
                }
            }

            //还有更多数据
            if($count > $start + 1000){
                return array($start + 1000, $count);
            }
        }

        //备份下一表
        return 0;
    }

    public function import($start){
        //还原数据
        $db = self::connect();

        if($this->config['compress']){
            $gz   = gzopen($this->file[1], 'r');
            $size = 0;
        } else {
            $size = filesize($this->file[1]);
            $gz   = fopen($this->file[1], 'r');
        }

        $sql  = '';
        if($start){
            $this->config['compress'] ? gzseek($gz, $start) : fseek($gz, $start);
        }

        for($i = 0; $i < 1000; $i++){
            $sql .= $this->config['compress'] ? gzgets($gz) : fgets($gz);
            if(preg_match('/.*;$/', trim($sql))){
                if(false !== $db->execute($sql)){
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
                return 0;
            }
        }

        return array($start, $size);
    }
    /**
     * 属性修改器 设置数据对象值
     * @access public
     * @param string(array) $name  属性名
     * @param mixed  $value 属性值
     * @return $this
     */
    public function setAttr($name,$value=''){
        if(is_array($name)){
            foreach ($name as $key=>$value){
                $this->$key = $value;
            }
        }else{
            $this->$name = $value;
        }
        return $this;
    }
    /**
     * 析构方法，用于关闭文件资源
     */
    public function __destruct(){
//        $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
    }
}