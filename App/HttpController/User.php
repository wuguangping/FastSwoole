<?php
/**
 * FastSwoole - A Swoole Framework For EasySwoole
 *
 * @package FastSwoole
 * @author  wuguangping (Goh) <wuguangping@qq.com>
 */

namespace App\HttpController;

use App\Model\UserModel;
use FastSwoole\Controller;
use FastSwoole\Db;

class User extends Controller
{
    /**
     * DB快捷查询，仅为方便TP用户
     */
    function db()
    {
        $list = Db::name('user')->where('id', 2, '>')
            ->field('id, username')
            ->select();
        $this->success(['list' => $list]);
    }

    /**
     * DB快捷查询，带分页查询两次数据库
     */
    function db_total()
    {
        $list = Db::name('user')->where('id', 2, '>')
            ->field('id, username')
            ->select();
        $total = Db::name('user')->where('id', 2, '>')->count();
        $this->success(['list' => $list, 'total' => $total]);
    }

    /**
     * 模型查询
     */
    function model()
    {
        $list = UserModel::init()->where('id', 2, '>')
            ->get(null, 'id, username');
        $this->success(['list' => $list]);
    }

    /**
     * 模型查询，带分页查询一次数据库
     */
    function model_total()
    {
        // 分页参数
        $numRows = $this->getNumRows();

        $result = UserModel::init()->where('id', 2, '>')
            ->get($numRows, 'id, username', true);
        $this->success(['list' => $result['list'], 'total' => $result['total']]);
    }
}