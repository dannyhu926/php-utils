<?php
namespace Utils\Search\Hive;

use ThriftSQL\Exception;
use ThriftSQL\Hive;

/**
 * Class HiveHelper.
 */
class HiveHelper
{
    /** @var HiveHelper */
    private static $instance;
    /** @var Hive */
    private static $hive;

    /**
     * @param string $host
     * @param string $userName
     * @param string $paasswd
     * @param int    $port
     * @param int    $timeOut
     *
     * @return Hive
     */
    public static function getInstance($host, $userName, $paasswd = 'test', $port = 10000, $timeOut = 100)
    {
        try {
            if (!(self::$instance instanceof self) || empty(self::$hive) || empty(self::$hive->query('select unix_timestamp()')->wait()->fetch(1))) {
                self::$instance = new self();

                self::$hive = new Hive($host, $port, $userName, $paasswd, $timeOut);

                self::$hive->setSasl(true);
                self::$hive->connect();
            }
        } catch (Exception $exception) {
        }

        return self::$hive;
    }

    /**
     * 断开Hive连接
     */
    public function __destruct()
    {
        try {
            if (!empty(self::$hive) && !empty(self::$hive->query('select unix_timestamp()')->wait()->fetch(1))) {
                self::$hive->disconnect();
            }
        } catch (Exception $exception) {
        }
    }

    /**
     * HiveHelper constructor.
     */
    private function __construct()
    {
    }

    /**
     *覆盖clone()方法，禁止克隆.
     */
    private function __clone()
    {
        // 覆盖clone()方法，禁止克隆
    }
}
