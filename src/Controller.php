<?php
/**
 * FastSwoole - A Swoole Framework For EasySwoole
 *
 * @package FastSwoole
 * @author  wuguangping (Goh) <wuguangping@qq.com>
 */

namespace FastSwoole;

use EasySwoole\Http\AbstractInterface\Controller as AbstractController;
use FastSwoole\Utility\Code;

class Controller extends AbstractController
{
    /**
     * 模型分页查询参数
     * @example [起始, 行数]
     * @var array
     */
    protected $numRows = [];

    /**
     * 默认请求
     */
    function index()
    {
        // TODO: Implement index() method.
    }

    /**
     * 重写请求
     * @param string|null $action
     * @return bool|null
     */
    protected function onRequest(?string $action): ?bool
    {
        if (parent::onRequest($action)) {

            // 分页参数
            $page          = $this->param('page', 1); // 页数
            $rows          = $this->param('rows', 20); // 行数
            $num           = ($page - 1) * $rows;
            $num           = $num < 0 ? 0 : $num; // 起始
            $this->numRows = [$num, $rows];

            return true;
        }
        return false;
    }

    /**
     * 获取 POST 参数
     * @param string $name    参数名
     * @param null   $default 默认值
     * @return array|int|string|null
     */
    function post(string $name = '', $default = null)
    {
        $post = $this->request()->getParsedBody();
        return $this->input($post, $name, $default);
    }

    /**
     * 获取 GET 参数
     * @param string $key     参数名
     * @param null   $default 默认值
     * @return array|int|string|null
     */
    function get(string $key = '', $default = null)
    {
        $post = $this->request()->getQueryParams();
        return $this->input($post, $key, $default);
    }

    /**
     * 获取 REQUEST 参数
     * @param string $key     参数名
     * @param null   $default 默认值
     * @return array|int|string|null
     */
    function param(string $key = '', $default = null)
    {
        $data = $this->request()->getRequestParam();
        return $this->input($data, $key, $default);
    }

    /**
     * 获取参数 支持默认值
     * @param array  $data    数据源
     * @param string $name    参数名
     * @param null   $default 默认值
     * @return array|int|string|null
     */
    function input(array $data, string $name = '', $default = null)
    {
        // 默认返回所有数据
        if (empty($name)) {
            return $data;
        }
        $name = (string)$name;

        // 获取参数
        if (isset($data[$name])) {
            $result = $data[$name];
        } else {
            return $default;
        }

        // 对象直接返回
        if (is_object($result)) {
            return $result;
        }

        // 解析参数类型
        if (!empty($default) && $result !== $default) {
            if (is_string($default)) {
                // 字符串
                $result = (string)$result;
            } else if (is_int($default)) {
                // 整形
                $result = (int)$result;
            } else if (is_float($default)) {
                // 浮点
                $result = (float)$result;
            } else if (is_bool($default)) {
                // 布尔
                $result = (bool)$result;
            } else if (is_array($default)) {
                // 数组
                $result = (array)$result;
            }
        }

        return $result;
    }

    /**
     * 操作成功返回数据
     * @param array  $result 要返回的数据
     * @param string $msg    提示信息
     * @param int    $code   错误码
     * @return bool
     */
    protected function success(array $result = [], string $msg = '', int $code = 0)
    {
        return $this->result($msg, $result, $code);
    }

    /**
     * 操作失败返回数据
     * @param int    $code   错误码
     * @param array  $result 要返回的数据
     * @param string $msg    提示信息
     * @return bool
     */
    protected function error(int $code = 1, array $result = [], string $msg = '')
    {
        return $this->result($msg, $result, $code);
    }

    /**
     * 返回封装后的数据到客户端
     * @param string $msg    提示信息
     * @param array  $result 要返回的数据
     * @param int    $code   错误码
     * @return bool
     */
    protected function result(string $msg, array $result = [], int $code = 1)
    {
        if (!$this->response()->isEndResponse()) {
            $this->response()->withAddedHeader('Access-Control-Allow-Origin', '*');
            $this->response()->withAddedHeader('Content-Type', 'application/json; charset=utf-8');
            $this->response()->withAddedHeader('Access-Control-Allow-Headers', 'X-Requested-With,Content-Type,Token,User-Id,Request-Url,Source,Longitude,Latitude,Wechat-Openid,Authorization');
            $this->response()->withAddedHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
            $this->response()->withAddedHeader('Server', 'zhyp');

            $this->response()->withStatus(200);

            $data = ['code' => $code, 'msg' => $msg ? $msg : Code::getReasonPhrase($code), 'result' => $result];
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            return true;
        } else {
            trigger_error("success: response has end");
            return false;
        }
    }

}