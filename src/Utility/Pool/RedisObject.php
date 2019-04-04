<?php
/**
 * FastSwoole - A Swoole Framework For EasySwoole
 *
 * @package FastSwoole
 * @author  wuguangping (Goh) <wuguangping@qq.com>
 */

namespace ESwoole\Utility\Pool;

use EasySwoole\Component\Pool\PoolObjectInterface;
use Swoole\Coroutine\Redis;

class RedisObject extends Redis implements PoolObjectInterface
{
    function gc()
    {
        // TODO: Implement gc() method.
        $this->close();
    }

    function objectRestore()
    {
        // TODO: Implement objectRestore() method.
        // echo "objectRestore\n";
    }

    function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }

}