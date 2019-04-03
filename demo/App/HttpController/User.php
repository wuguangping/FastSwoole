<?php
/**
 * FastSwoole - A PHP Framework For EasySwoole
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
    function db()
    {
        $this->success(Db::name('user')->where('id', 2, '>')->field('id, username')->select());
    }

    function model()
    {
        $this->success(UserModel::init()->where('id', 2, '>')->get(null, 'id, username'));
    }
}