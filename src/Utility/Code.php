<?php
/**
 * FastSwoole - A PHP Framework For EasySwoole
 *
 * @package FastSwoole
 * @author  wuguangping (Goh) <wuguangping@qq.com>
 */

namespace FastSwoole\Utility;

class Code
{
    /**
     * 基本信息 x
     */
    const SUCCESS = 0;
    const ERROR   = 1;

    private static $phrases = [
        /**
         * 基本信息 x
         */
        0 => '成功',
        1 => '错误',
    ];

    public static function getReasonPhrase($code)
    {
        if (isset(self::$phrases[$code])) {
            return self::$phrases[$code];
        } else {
            return null;
        }
    }
}