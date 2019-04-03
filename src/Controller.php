<?php


namespace FastSwoole;


use EasySwoole\Http\AbstractInterface\Controller as AbstractController;
use FastSwoole\Utility\Code;

class Controller extends AbstractController
{
    function index()
    {
        // TODO: Implement index() method.
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
            return TRUE;
        } else {
            trigger_error("success: response has end");
            return FALSE;
        }
    }

}