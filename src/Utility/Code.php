<?php


namespace ESwoole\Utility;


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
            return NULL;
        }
    }
}