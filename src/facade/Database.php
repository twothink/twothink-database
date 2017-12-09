<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 苹果  593657688@qq.com <www.twothink.cn> 
// +----------------------------------------------------------------------
namespace twothink\facade;

use think\Facade;
/*
 * @Author: 苹果  <593657688@qq.com>
 */
/**
 * @see \twothink\Database
 * @mixin \twothink\Database
 * @method array  getDataList($table = null,$type=1) static 获取数据库表列表
 * @method string optimize($table = null) static 优化表
 * @method string repair($table = null) static 修复表
 * @method string backupinit($table) static 备份数据库初始化
 * @method string create() static  创建备份文件
 * @method arraay backup($table, $start) static 备份表结构
 * @method string delFile($time) static 删除备份文件
 * @method string fileList($path=false) static 备份文件列表
 * @method string import($start) //还原导入
 * @method string downloadFile($time, $part = 0) //下载备份文件
 * @method object setAttr($name,$value='') static 设置类属性  属性修改器
 * @method object handler() static 返回句柄对象，可执行其它高级方法
 */
class Database extends Facade
{
    protected static function getFacadeClass()
    {
        return 'twothink\Database';
    }
}