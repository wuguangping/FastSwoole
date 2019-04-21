<?php
/**
 * FastSwoole - A Swoole Framework For EasySwoole
 *
 * @package FastSwoole
 * @author  wuguangping (Goh) <wuguangping@qq.com>
 */

namespace FastSwoole;

use EasySwoole\Component\Pool\PoolManager;
use EasySwoole\EasySwoole\Config;
use EasySwoole\EasySwoole\Logger;
use EasySwoole\Mysqli\Exceptions\ConnectFail;
use EasySwoole\Mysqli\Exceptions\JoinFail;
use EasySwoole\Mysqli\Exceptions\Option;
use EasySwoole\Mysqli\Exceptions\OrderByFail;
use EasySwoole\Mysqli\Exceptions\PrepareQueryFail;
use EasySwoole\Mysqli\Exceptions\WhereParserFail;
use EasySwoole\Mysqli\Mysqli;
use EasySwoole\Spl\SplString;
use FastSwoole\Utility\Pool\MysqlObject;
use FastSwoole\Utility\Pool\MysqlPool;

class Model
{
    /**
     * 先创建Mysqli的实例
     *
     * @var Mysqli
     */
    private $db;

    /**
     * 数据库前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 对象的表名。默认情况下将使用类名
     *
     * @var string
     */
    protected $tableName = '';

    /**
     * 默认输出的字段
     * @var array
     */
    protected $columns = '*';

    /**
     * 是否使用where调用
     * @var array
     */
    protected $isWhere = false;

    /**
     * 保存对象数据的数组
     *
     * @var array
     */
    public $data;

    /**
     * 对象的主键。'id'是默认值。
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 是否自动记录创建时间
     * @var bool
     */
    protected $createTime = false;
    /**
     * 记录创建时间字段名
     * @var string
     */
    protected $createTimeName = 'create_time';

    /**
     * 是否开启软删除
     * @var bool
     */
    protected $softDelete = false;
    /**
     * 软删除时间字段名
     * @var string
     */
    protected $softDeleteTimeName = 'delete_time';

    protected $throwable;

    /**
     * Model constructor.
     */
    function __construct()
    {
        if (empty($this->prefix)) {
            $this->prefix = Config::getInstance()->getConf('MYSQL.prefix');
        }

        if (empty($this->tableName)) {
            // 未定表名取类名
            $split = explode("\\", get_class($this));
            $end   = end($split);
            // 大写骆峰式命名的文件转为下划线区分表 TODO 未来可以增加配置开关是否需要
            $splString = new SplString($end);
            $tableName = $splString->snake('_')->__toString();
            // 删除后缀_Model TODO 未来可以增加配置开关是否需要
            $tableName       = substr($tableName, 0, -6);
            $this->tableName = $this->prefix . $tableName;
        }

        // 获取连接
        $db = PoolManager::getInstance()->getPool(MysqlPool::class)->getObj(Config::getInstance()->getConf('MYSQL.POOL_TIME_OUT'));
        if ($db instanceof MysqlObject) {
            $this->setDb($db);
        } else {
            Logger::getInstance()->console('MysqlPool Empty', $this->tableName);
            return null;
        }
    }

    function __destruct()
    {
        $db = $this->getDb();
        if ($db instanceof MysqlObject) {
            $db->gc();
            PoolManager::getInstance()->getPool(MysqlPool::class)->recycleObj($db);
            $db = null;
        }
    }

    /**
     * 带前缀
     * @return string
     */
    function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param $db
     * @return $this|null
     */
    function setDb($db)
    {
        if ($db instanceof Mysqli) {
            $this->db = $db;
            return $this;
        } else {
            return null;
        }
    }

    /**
     * @return Mysqli
     */
    function getDb(): Mysqli
    {
        return $this->db;
    }

    /**
     * 批量增加条件
     * @param array $condition
     */
    function addCondition($condition = [])
    {
        $allow = ['where', 'orWhere', 'join', 'orderBy', 'groupBy'];
        foreach ($condition as $k => $v) {
            if (in_array($k, $allow)) {
                foreach ($v as $item) {
                    $this->getDb()->$k(...$item);
                }
            }
        }
        return $this;
    }

    /**
     * 开启查询跟踪
     */
    function startTrace()
    {
        $this->getDb()->startTrace();
    }

    /**
     * 结束查询跟踪并返回结果
     * @return array
     */
    function endTrace()
    {
        return $this->getDb()->endTrace();
    }

    /**
     * 执行原始查询语句
     * @param string $query      需要执行的语句
     * @param array  $bindParams 如使用参数绑定语法 请传入本参数
     * @return mixed 被执行语句的查询结果
     */
    function rawQuery($query, array $bindParams = [])
    {
        try {
            return $this->getDb()->rawQuery($query, $bindParams);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 开启事务
     * @return bool 是否成功开启事务
     */
    function startTransaction(): bool
    {
        try {
            return $this->getDb()->startTransaction();
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 提交事务
     * @return bool 是否成功提交事务
     */
    function commit(): bool
    {
        try {
            return $this->getDb()->commit();
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 回滚事务
     * @param bool $commit
     * @return array|bool
     */
    function rollback($commit = true)
    {
        try {
            return $this->getDb()->rollback($commit);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 添加一个WHERE条件
     * @example UserModel::init()->where('status', 0)->where('id', 100, '>')->...
     * @param string $whereProp  字段名
     * @param string $whereValue 字段值
     * @param string $operator   字段操作
     * @param string $cond       多个where的逻辑关系
     * @return $this
     */
    function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->getDb()->where($whereProp, $whereValue, $operator, $cond);
        return $this;
    }

    /**
     * 添加一个WHERE OR条件
     * @param string $whereProp  字段名
     * @param string $whereValue 字段值
     * @param string $operator   字段操作
     * @return $this
     */
    function whereOr($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        $this->getDb()->whereOr($whereProp, $whereValue, $operator);
        return $this;
    }

    /**
     * 字段是Null值
     * @param string $whereProp 字段名
     * @param string $cond      多个where的逻辑关系
     * @return $this
     */
    function whereNull($whereProp, $cond = 'AND')
    {
        $this->getDb()->whereNull($whereProp, $cond);
        return $this;
    }

    /**
     * 字段是非NULL值
     * @param string $whereProp 字段名
     * @param string $cond      多个where的逻辑关系
     * @return $this
     */
    function whereNotNull($whereProp, $cond = 'AND')
    {
        $this->getDb()->whereNotNull($whereProp, $cond);
        return $this;
    }

    /**
     * 字段是空字符串
     * @param string $whereProp 字段名
     * @param string $cond      多个where的逻辑关系
     * @return $this
     */
    function whereEmpty($whereProp, $cond = 'AND')
    {
        $this->getDb()->whereEmpty($whereProp, $cond);
        return $this;
    }

    /**
     * 字段是非空字符串
     * @param string $whereProp 字段名
     * @param string $cond      多个where的逻辑关系
     * @return $this
     */
    function whereNotEmpty($whereProp, $cond = 'AND')
    {
        $this->getDb()->whereEmpty($whereProp, $cond);
        return $this;
    }

    /**
     * 字段值在列表中
     * @param string       $whereProp  字段名
     * @param string|array $whereValue 列表 可传数组或逗号分隔
     * @param string       $cond       多个where的逻辑关系
     * @return $this
     */
    function whereIn($whereProp, $whereValue, $cond = 'AND')
    {
        $this->getDb()->whereIn($whereProp, $whereValue, $cond);
        return $this;
    }

    /**
     * 字段值不在列表中
     * @param string       $whereProp  字段名
     * @param string|array $whereValue 列表 可传数组或逗号分隔
     * @param string       $cond       多个where的逻辑关系
     * @return $this
     */
    function whereNotIn($whereProp, $whereValue, $cond = 'AND')
    {
        $this->getDb()->whereNotIn($whereProp, $whereValue, $cond);
        return $this;
    }

    /**
     * 在两者之间
     * @example UserModel::init()->whereBetween('id', '1,2')->get()
     * @param string       $whereProp  字段名
     * @param string|array $whereValue 可传数组或逗号分隔 [ 1 , 2 ] OR '1,2'
     * @param string       $cond       多个where的逻辑关系
     * @return $this
     */
    function whereBetween($whereProp, $whereValue, $cond = 'AND')
    {
        try {
            $this->getDb()->whereBetween($whereProp, $whereValue, $cond);
        } catch (WhereParserFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        }
        return $this;
    }

    /**
     * 不在两者之间
     * @example UserModel::init()->whereNotBetween('id', '1,2')->get()
     * @param string       $whereProp  字段名
     * @param string|array $whereValue 可传数组或逗号分隔 [ 1 , 2 ] OR '1,2'
     * @param string       $cond       多个where的逻辑关系
     * @return $this
     */
    function whereNotBetween($whereProp, $whereValue, $cond = 'AND')
    {
        try {
            $this->getDb()->whereNotBetween($whereProp, $whereValue, $cond);
        } catch (WhereParserFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        }
        return $this;
    }

    /**
     * WHERE LIKE
     * @param string $whereProp  字段名
     * @param string $whereValue 字段值
     * @param string $cond       多个where的逻辑关系
     * @return $this
     */
    function whereLike($whereProp, $whereValue, $cond = 'AND')
    {
        $this->getDb()->whereLike($whereProp, $whereValue, $cond);
        return $this;
    }

    /**
     * WHERE NOT LIKE
     * @param string $whereProp  字段名
     * @param string $whereValue 字段值
     * @param string $cond       多个where的逻辑关系
     * @return $this
     */
    function whereNotLike($whereProp, $whereValue, $cond = 'AND')
    {
        $this->getDb()->whereNotLike($whereProp, $whereValue, $cond);
        return $this;
    }

    /**
     * SELECT 查询数据
     * @example UserModel::init()->where('status', 1)->get('id, name')
     * @param string       $columns        需要返回的字段
     * @param null|integer $numRows        需要返回的行数
     * @param bool         $withTotalCount 是否返回查询结果总数
     * @param string       $tableName      表名称
     * @return array|mixed
     */
    function get($columns = '*', $tableName = '', $numRows = null)
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        $columns         = $columns ? $columns : $this->columns;
        try {
            return $this->getDb()->get($this->tableName, $numRows, $columns);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * SELECT 查询数据带 TOTAL
     * @example UserModel::init()->where('status', 1)->get([0, 20], 'id, name', true)
     * @param string       $columns        需要返回的字段
     * @param null|integer $numRows        需要返回的行数
     * @param bool         $withTotalCount 是否返回查询结果总数
     * @param string       $tableName      表名称
     * @return array|mixed
     */
    function getWithTotalCount($numRows = null, $columns = '*', $tableName = '')
    {
        $this->withTotalCount();
        $list  = $this->get($columns, $tableName, $numRows);
        $total = $this->getTotalCount();
        return ['list' => $list, 'total' => $total];
    }

    /**
     * SELECT LIMIT 1 查询单条数据
     * @example UserModel::init()->where('status', 1)->getOne()
     * @param string $columns   需要返回的字段
     * @param string $tableName 表名称
     * @return Mysqli|mixed|null
     */
    function getOne($columns = '*', $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->getOne($this->tableName, $columns);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 获取某一个字段的值
     * @param string $column    需要返回的字段
     * @param int    $limit     限制返回的行数
     * @param string $tableName 表名称
     * @return array|bool|null
     */
    function getValue($column, $limit = 1, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->getValue($this->tableName, $column, $limit);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 获取某一列的数据
     * @param string $columnName 需要获取的列名称
     * @param null   $limit      最多返回几条数据
     * @param string $tableName  表名称
     * @return array|bool
     */
    function getColumn($columnName, $limit = null, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->getColumn($this->tableName, $columnName, $limit);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 插入一行数据
     * @example UserModel::init()->insert(['status' => 1]);
     * @param array  $insertData
     * @param string $tableName 表名称
     * @return bool|int
     */
    function insert($insertData, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->insert($this->tableName, $insertData);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 替换插入一行数据
     * @example UserModel::init()->replace(['status' => 1]);
     * @param array  $insertData
     * @param string $tableName 表名称
     * @return bool|int|null
     */
    function replace($insertData, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->replace($this->tableName, $insertData);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 此函数存储更新列的名称和自动增量列的列名
     * @param array  $updateColumns 带值的变量
     * @param string $lastInsertId  变量值
     * @return $this
     */
    function onDuplicate($updateColumns, $lastInsertId = null)
    {
        $this->getDb()->onDuplicate($updateColumns, $lastInsertId);
        return $this;
    }

    /**
     * 插入多行数据
     * @param array      $multiInsertData 需要插入的数据
     * @param array|null $dataKeys        插入数据对应的字段名
     * @param string     $tableName       表名称
     * @return array|bool
     */
    function insertMulti(array $multiInsertData, array $dataKeys = null, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->insertMulti($this->tableName, $multiInsertData, $dataKeys);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 该查询条件下是否存在数据
     * @example UserModel::init()->where('status', 1)->has()
     * @param string $tableName 查询的表名称
     * @param string $tableName 表名称
     * @return bool
     */
    function has($tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->has($this->tableName);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (Option $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 聚合-计算总数
     * @example UserModel::init()->where('status', 1)->count('id')
     * @param string|null $filedName 字段名称
     * @param string      $tableName 表名称
     * @return mixed
     */
    function count($filedName = null, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        return $this->getDb()->count($this->tableName, $filedName);
    }

    /**
     * 聚合-求最大值
     * @param string $filedName 字段名称
     * @param string $tableName 表名称
     * @return mixed
     */
    function max($filedName, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->max($this->tableName, $filedName);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 聚合-求最小值
     * @param string $filedName 字段名称
     * @param string $tableName 表名称
     * @return mixed
     */
    function min($filedName, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->min($this->tableName, $filedName);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 聚合-计算和值
     * @param string $filedName 字段名称
     * @param string $tableName 表名称
     * @return mixed
     */
    function sum($filedName, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->sum($this->tableName, $filedName);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 聚合-求平均值
     * @param string $filedName 字段名称
     * @param string $tableName 表名称
     * @return mixed
     */
    function avg($filedName, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->avg($this->tableName, $filedName);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 删除数据
     * @example UserModel::init()->where('id', 1)->delete()
     * @param null|integer $numRows   限制删除的行数
     * @param string       $tableName 表名称
     * @return bool|null
     */
    function delete($numRows = null, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->delete($this->tableName, $numRows);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 设置单个字段的值 (属于Update的快捷方法 )
     * 可用于快速更改某个字段的状态
     * @example UserModel::init()->whereIn('id', '1,2,3,4')->setValue('status', 1)
     * @param        $tableName
     * @param        $filedName
     * @param        $value
     * @param string $tableName 表名称
     * @return mixed
     */
    function setValue($filedName, $value, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->setValue($this->tableName, $filedName, $value);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 更新数据
     * @example UserModel::init()->where('id', 1)->update(['status' => 1]);
     * @param array        $tableData 需要更新的数据
     * @param null|integer $numRows   限制更新的行数
     * @param string       $tableName 表名称
     * @return mixed
     */
    function update($tableData, $numRows = null, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->update($this->tableName, $tableData, $numRows);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 表是否存在
     * @param string $tableName 表名称
     * @return bool
     */
    function tableExists($tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->tableExists($this->tableName);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 执行字段自增操作
     * @param int|float $num
     * @return array
     */
    function inc($num = 1)
    {
        return $this->getDb()->inc($num);
    }

    /**
     * 执行字段自减操作
     * @param int|float $num
     * @return array
     */
    function dec($num = 1)
    {
        return $this->getDb()->dec($num);
    }

    /**
     * 自增某个字段
     * @example UserModel::init()->where('id', 1)->setInc('level', 1)
     * @param string    $filedName 操作的字段名称
     * @param int|float $num       操作数量
     * @param string    $tableName 表名称
     * @return mixed
     * @TODO    set inc after lock some line
     */
    function setInc($filedName, $num = 1, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->setInc($this->tableName, $filedName, $num);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 自减某个字段
     * @example UserModel::init()->where('id', 1)->where('level', 1, '>')->setInc('level', 1)
     * @param string    $tableName 表名称
     * @param string    $filedName 操作的字段名称
     * @param int|float $num       操作数量
     * @param string    $tableName 表名称
     * @return mixed
     * @TODO    set dec after lock some line
     */
    function setDec($filedName, $num = 1, $tableName = '')
    {
        $this->tableName = $tableName ? $tableName : $this->tableName;
        try {
            return $this->getDb()->setDec($this->tableName, $filedName, $num);
        } catch (ConnectFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (PrepareQueryFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        } catch (\Throwable $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
            return false;
        }
    }

    /**
     * 获取即将执行的SQL语句
     * @param bool $fetch
     * @return $this
     */
    function fetchSql(bool $fetch = true)
    {
        $this->getDb()->fetchSql($fetch);
        return $this;
    }

    /**
     * 查询结果总数
     * @example $userModel = new UserModel()->where('status', 1)->whithTotalCount()->get()
     * @return $this
     */
    public function withTotalCount()
    {
        try {
            $this->getDb()->withTotalCount();
        } catch (Option $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        }
        return $this;
    }

    /**
     * 返回结果总数
     * @example $userModel->getTotalCount()
     * @return int
     */
    function getTotalCount(): int
    {
        return $this->getDb()->getTotalCount();
    }

    /**
     * 本次查询影响的行数
     * @return int
     */
    function getAffectRows(): int
    {
        return $this->getDb()->getAffectRows();
    }

    /**
     * 表连接查询
     * @param string $joinTable     被连接的表
     * @param string $joinCondition 连接条件
     * @param string $joinType      连接类型
     * @return $this
     */
    function join($joinTable, $joinCondition, $joinType = '')
    {
        try {
            $this->getDb()->join($joinTable, $joinCondition, $joinType);
        } catch (JoinFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        }
        return $this;
    }

    /**
     * 表左连接查询
     * @param string $joinTable     被连接的表
     * @param string $joinCondition 连接条件
     * @return $this
     */
    function leftJoin($joinTable, $joinCondition)
    {
        return $this->join($joinTable, $joinCondition, 'LEFT');
    }

    /**
     * 表右连接查询
     * @param string $joinTable     被连接的表
     * @param string $joinCondition 连接条件
     * @return $this
     */
    function rightJoin($joinTable, $joinCondition)
    {
        return $this->join($joinTable, $joinCondition, 'RIGHT');
    }

    /**
     * 设置额外查询参数
     * @param mixed $options 查询参数 可传入数组设置多个
     * @return $this
     */
    public function setQueryOption($options)
    {
        try {
            $this->getDb()->setQueryOption($options);
        } catch (Option $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        }
        return $this;
    }

    function getLastStatement()
    {
        return $this->getDb()->getLastStatement();
    }

    /**
     * 获取子查询
     * @return array|null
     */
    function getSubQuery()
    {
        return $this->getDb()->getSubQuery();
    }

    /**
     * 创建子查询
     * @param string $subQueryAlias
     * @return $this
     */
    function subQuery($subQueryAlias = '')
    {
        $this->getDb()->subQuery($subQueryAlias);
        return $this;
    }

    /**
     * 获取最后插入的数据ID
     * @return int
     */
    public function getInsertId()
    {
        return $this->getInsertId();
    }

    /**
     * 获取最后一次查询的语句
     * @return mixed
     */
    public function getLastQuery()
    {
        return $this->getDb()->getLastQuery();
    }

    /**
     * 获取最后一次查询错误的内容
     * @return string
     */
    public function getLastError()
    {
        return $this->getDb()->getLastError();
    }

    /**
     * 获取最后一次查询错误的编号
     * @return mixed
     */
    public function getLastErrno()
    {
        return $this->getDb()->getLastErrno();
    }

    /**
     * 字段排序
     * @param        $orderByField
     * @param string $orderByDirection
     * @param null   $customFieldsOrRegExp
     * @return $this
     */
    public function orderBy($orderByField, $orderByDirection = 'DESC', $customFieldsOrRegExp = null)
    {
        try {
            $this->getDb()->orderBy($orderByField, $orderByDirection, $customFieldsOrRegExp);
        } catch (OrderByFail $e) {
            Logger::getInstance()->console($e->getMessage(), $this->tableName);
        }
        return $this;
    }

    /**
     * 字段分组
     * @param $groupByField
     * @return $this
     */
    public function groupBy($groupByField)
    {
        $this->getDb()->groupBy($groupByField);
        return $this;
    }

    /*
     * 可以在此临时修改timeout
     */
    public function getConfig()
    {
        return $this->getDb()->getConfig();
    }

    /**
     * @return Model
     */
    static function init()
    {
        return new static();
    }

}