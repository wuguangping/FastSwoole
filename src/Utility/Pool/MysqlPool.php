<?php
/**
 * FastSwoole - A Swoole Framework For EasySwoole
 *
 * @package FastSwoole
 * @author  wuguangping (Goh) <wuguangping@qq.com>
 */

namespace FastSwoole\Utility\Pool;

use EasySwoole\Component\Pool\AbstractPool;
use EasySwoole\EasySwoole\Config;

class MysqlPool extends AbstractPool
{
    /**
     * 请在此处返回一个数据库链接实例
     * @return MysqlObject
     */
    protected function createObject()
    {
        // TODO: Implement createObject() method.
        // 当连接池第一次获取连接时,会调用该方法
        // 我们需要在该方法中创建连接
        // 返回一个对象实例
        // 必须要返回一个实现了 AbstractPoolObject 接口的对象
        $conf   = Config::getInstance()->getConf("MYSQL");
        $dbConf = new \EasySwoole\Mysqli\Config($conf);
        return new MysqlObject($dbConf);
    }

}