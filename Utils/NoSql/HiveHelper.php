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
     * 为hive查询的数据源加入key.
     *
     * @param mixed $columns
     * @param array $data
     * @param bool  $hasKey
     *
     * @return array|bool
     */
    public static function formatHiveDataPresto($columns, array $data, bool $hasKey = true)
    {
        $result = false;
        if (!empty($columns) && !empty($data)) {
            $arrColumns = explode(',', $columns);
            $renameColumns = [];
            foreach ($arrColumns as $item) {
                $renameColumns[] = "$item";
            }
            if ($hasKey) {
                $renameColumns[] = 'k';
            }
            if ($data) {
                foreach ($data as $item) {
                    if (is_array($item)) {
                        $child = [];
                        $tmp = array_values($item);
                        foreach ($renameColumns as $k => $name) {
                            $child = array_add($child, str_contains($name, '|') ? str_replace('|', ',', $name) : $name, empty($tmp[$k]) ? 0 : $tmp[$k]);
                        }
                        $result[] = $child;
                    } else {
                        $result[$columns] = $item;
                    }
                }
            }
        }

        return $result;
    }
	
    /**
     * @param string $columns
     *
     * @return bool
     */
    public function splitColumns(string $columns)
    {
        $result = false;
        if ($columns) {
            $columns = explode(',', $columns);
            foreach ($columns as $item) {
                $result[$item] = 0;
            }
        }

        return $result;
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
