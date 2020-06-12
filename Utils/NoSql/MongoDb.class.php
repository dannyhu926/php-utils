<?php
/**
 * mongodb 常用类
 */

namespace App\Utility;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteConcern;
use Psr\Log\LoggerInterface;

/**
 * Class Mongo
 */
class MongoDb
{
    /** @var LoggerInterface 日志 */
    private $logger;

    private $dsn;

    private $database;

    private static $conn = [];  //保存连接


    /**
     * MongoDBCommon constructor.
     *
     * @param LoggerInterface $logger
     * @param Amqp            $amqp
     * @param string $dsn      //env中配置的参数
     * @param string $database
     *
     */
    public function __construct(LoggerInterface $logger, Amqp $amqp, string $dsn = "", string $database = "")
    {
        $this->dsn = empty($dsn) ? "MONGODB_URI": $dsn;
        $this->database = empty($database) ? getenv("MONGODB_DATABASE") : $database;
        $this->logger = $logger;
    }

    /**
     * @param array  $documents
     * @param string $collectionName
     *
     * @return mixed 返回成功插入的条数
     */
    public function insert($documents, $collectionName)
    {
        $manager = $this->connect();
        try {
            $bulk = new BulkWrite();
            foreach ($documents as $document) {
                $bulk->insert($document);
            }
            $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $result = $manager->executeBulkWrite($this->database.".".$collectionName, $bulk, $writeConcern);

            return $result->getInsertedCount();
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Mongo数据库【%s】多条数据插入失败,数据%s,原因%s', $collectionName, json_encode($documents), $exception->getMessage()));

            return false;
        }
    }

    /**
     * 根据id组删除所有.
     *
     * @param array  $ids
     * @param string $collectionName
     *
     * @return int
     */
    public function delete($ids, $collectionName)
    {
        $manager = $this->connect();
        try {
            $bulk = new BulkWrite();
            foreach ($ids as $id) {
                $bulk->delete(['_id' => (int) $id]);
            }
            $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $result = $manager->executeBulkWrite($this->database.'.'.$collectionName, $bulk, $writeConcern);
			
            return $result->getDeletedCount();
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Mongo数据库删除【%s:%s】数据失败，原因%s', $collectionName, json_encode($ids), $exception->getMessage()));

            return false;
        }
    }

    /**
     * 更新操作
     *
     * @param array|int $filter
     * @param array     $update
     * @param string    $collectionName
     * @param string    $method         set/inc两种方式更新
     * @param bool      $upsert
     *
     * @return mixed 返回修改个数
     */
    public function update($filter, array $update, string $collectionName, $method = "set", $upsert = false)
    {
        $manager = $this->connect();
        try {
            $bulk = new BulkWrite();
            if (is_int($filter)) {
                $id = $filter;
                $filter = ['_id' => $filter];
            }
            $bulk->update($filter, ['$'.$method => $update], ['upsert' => $upsert]);
            $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);
            $result = $manager->executeBulkWrite($this->database.'.'.$collectionName, $bulk, $writeConcern);

            return max($result->getModifiedCount(), $result->getUpsertedCount());
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Mongo数据库更新【%s:%s】数据失败,更新数据%s，原因%s', $collectionName, $id, json_encode($update), $exception->getMessage()));

            return false;
        }
    }

    /**
     * 查询接口
     *
     * @param array  $filter
     * @param string $collectionName
     * @param array  $options
     *
     * @return mixed
     *
     * @throws
     */
    public function fetch(array $filter, string $collectionName, $options = [])
    {
        $manager = $this->connect();
        try {
            $query = new Query($filter, $options);
            $res = $manager->executeQuery($this->database.'.'.$collectionName, $query);
            $items = [];
            if (!empty($res)) {
                foreach ($res as $v) {
                    $item = json_decode(json_encode($v), true);
                    if (is_numeric($item['_id']) || is_string($item['_id'])) {
                        $item['id'] = (string) $item['_id'];
                    }
                    $items[] = $item;
                }
            }
            unset($res, $v, $item);

            return $items;
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Mongo数据库数据获取【%s:%s】条件数据失败，原因%s', $collectionName, json_encode($filter), $exception->getMessage()));

            return false;
        }
    }

    /**
     * 链接函数
     * 配置一个static类获取mongodb连接
     */
    private function connect()
    {
        try {
            if (isset(self::$conn[$this->dsn]) && !empty(self::$conn[$this->dsn])) {
                return self::$conn[$this->dsn];
            }
            $manager = new Manager(getenv($this->dsn));
            self::$conn[$this->dsn] =  $manager;

            return $manager;
        } catch (\Exception $e) {
            //连接异常
            $this->logger->error(sprintf('连接到【Mongo:%s】失败，失败原因%s', $this->dsn, $e->getMessage()));
        }

        return null;
    }
}
