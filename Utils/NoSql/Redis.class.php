<?php
/**
 * redis缓存基础类
 */

namespace App\Utility;


class Redis
{
    private $dsn = "";

    private $logger;

    private static $conn = [];

    /**
     * Redis constructor.
     * @param string $dsn
     */
    public function __construct($dsn = "")
    {
        $this->dsn = empty($dsn) ? "REDIS_DSN" : $dsn;

        $this->logger = Logger::getInstance()->getObject();
    }

    /**
     * 获取连接
     *
     * @return mixed|\Redis
     */
    public function connect()
    {
        if (!empty(self::$conn[$this->dsn])) {
            return self::$conn[$this->dsn];
        }
        $addr = getenv($this->dsn);
        if (empty($addr)) {
            $this->logger->error(sprintf("【Redis: %s】连接配置为空", $this->dsn));

            return null;
        }
        $config = $this->parseConfig($addr);
        try {
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port']);
            if (isset($config['password']) && !empty($config['password'])) {
                $redis->auth($config['password']);
            }
            self::$conn[$this->dsn] = $redis;

            return $redis;
        } catch (\Exception $e) {
            $this->logger->error(sprintf("【Redis:%s】连接出错，错误原因：%s", $this->dsn, $e->getMessage()));
        }

        return null;
    }

    /**
     * 获取数据
     *
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        $redis = $this->connect();
        $res = "";
        if (!empty($redis)) {
            try {
                $res = $redis->get($key);
            } catch (\Exception $e) {
                $this->logger->error(sprintf("【Redis:%s】获取%s出错，错误原因%s", $this->dsn, $key, $e->getMessage()));
            }
        }

        return $res;
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return bool
     */
    public function set($key, $value, $ttl = 0)
    {
        $redis = $this->connect();
        $res = false;
        if (!empty($redis)) {
            try {
                $res = $redis->set($key, $value);
                if (!empty($ttl)) {
                    $res = $redis->expire($key, $ttl);
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf("【Redis:%s】获取%s出错，错误原因%s", $this->dsn, $key, $e->getMessage()));
            }
        }

        return $res;
    }

    /**
     * @param     $key
     * @param     $value
     * @param int $ttl
     *
     * @return bool
     */
    public function setnx($key, $value, $ttl = 0)
    {
        $redis = $this->connect();
        $res = false;
        if (!empty($redis)) {
            try {
                $res = $redis->setnx($key, $value);
                if ($res and !empty($ttl)) {
                    $redis->expire($key, $ttl);
                }
            } catch (\Exception $exception) {
                $this->logger->error(sprintf("【Redis:%s】获取%s出错，错误原因%s", $this->dsn, $key, $exception->getMessage()));
            }
        }

        return $res;
    }

    /**
     * @return \Redis
     */
    public function getRedisClient()
    {
        return $this->connect();
    }

    /**
     * 关闭连接
     * @param string $dsn
     */
    public function close($dsn)
    {
        if (!empty(static::$conn[$dsn])) {
            static::$conn[$dsn]->close();
            unset(static::$conn[$dsn]);
        }
    }

    /**
     * 解析参数
     *
     * @param string $addr
     *
     * @return array
     */
    private function parseConfig($addr)
    {
        $config = [];
        $scheme = parse_url($addr, PHP_URL_SCHEME);

        if ($host = parse_url($addr, PHP_URL_HOST)) {
            $config['host'] = $host;
        }
        if ($port = parse_url($addr, PHP_URL_PORT)) {
            $config['port'] = $port;
        }
//        if ($user = parse_url($addr, PHP_URL_USER)) {
//            $config['login'] = $user;
//        }
        if ($pass = parse_url($addr, PHP_URL_USER)) {
            $config['password'] = $pass;
        }

        return $config;
    }
}
