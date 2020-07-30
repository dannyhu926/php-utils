<?php
/**
 * 类说明：PhpSpreadsheet ExcelFileCache.php.
 *
 * $cache = new \Icsoc\Support\ExcelFileCache('/tmp/excel_cache');
 * \PhpOffice\PhpSpreadsheet\Settings::setCache($cache);
 */

namespace Utils;

use Psr\SimpleCache\CacheInterface;

class ExcelFileCache implements CacheInterface
{
    const FILE_SIZE = 5000; //读取时单次缓存行数（文件分割行数）

    private $cacheKey = [];
    private $cache = [];
    private $fileHandles = [];
    private $cacheDir;
    private $filePrefix;

    /**
     * FileCache constructor.
     *
     * @param $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/').'/';
        $this->filePrefix = uniqid();
    }

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $this->cacheKey = [];
        foreach ($this->fileHandles as $handle) {
            isset($handle) && fclose($handle);
        }
        $this->delCacheDir($this->cacheDir);

        return true;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        $key = $this->convertKey($key);
        unset($this->cacheKey[$key]);

        return true;
    }

    /**
     * @param iterable $keys
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function get($key, $default = null)
    {
        $key = $this->convertKey($key);
        if ($this->has($key)) {
            $seek = $this->cacheKey[$key];
            if (array_key_exists($key, $this->cache) && $this->cache[$key]['seek'] == $seek) {
                return $this->cache[$key]['data'];
            }
            $fp = $this->getFileHandleByKey($key);
            $this->cache = [];
            fseek($fp, 0);
            while (!feof($fp)) {
                $data = fgets($fp);
                $data = json_decode(trim($data), 1);
                if ($data['key'] == $key && $data['seek'] == $seek) {
                    $default = unserialize($data['data']);
                }
                $this->cache[$data['key']] = [
                    'data' => unserialize($data['data']),
                    'seek' => $data['seek'],
                ];
            }
        }

        return $default;
    }

    /**
     * @param iterable $keys
     * @param null     $default
     *
     * @return array|iterable
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }

        return $results;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        $key = $this->convertKey($key);

        return array_key_exists($key, $this->cacheKey);
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param null   $ttl
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set($key, $value, $ttl = null)
    {
        $key = $this->convertKey($key);
        if ($this->has($key) && $this->get($key) == $value) {
            return true;
        }
        $fp = $this->getFileHandleByKey($key);
        fseek($fp, 0, SEEK_END);
        $seek = ftell($fp);
        $this->cacheKey[$key] = $seek;
        fwrite($fp, json_encode([
                'key' => $key,
                'data' => serialize($value),
                'seek' => $seek,
            ]).PHP_EOL);
        unset($value);

        return true;
    }

    /**
     * @param iterable $values
     * @param null     $ttl
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    /**
     * @param $path
     */
    private function delCacheDir($path)
    {
        if (is_dir($path)) {
            foreach (scandir($path) as $val) {
                if ("." != $val && ".." != $val) {
                    if (is_dir($path.$val)) {
                        $this->delCacheDir($path.$val.'/');
                        @rmdir($path.$val.'/');
                    } else {
                        unlink($path.$val);
                    }
                }
            }
        }
    }

    /**
     * @param $key
     *
     * @return string
     */
    private function getFilenameByKey($key)
    {
        $arr = explode('.', $key);
        $end = array_pop($arr);
        $dir = $this->cacheDir.implode('_', $arr);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $line = '';
        $len = strlen($end);
        for ($i = 0; $i < $len; ++$i) {
            if (is_numeric($end[$i])) {
                $line .= $end[$i];
            }
        }
        $suf = (int) round($line / self::FILE_SIZE);

        return $dir.'/'.$this->filePrefix.$suf;
    }

    /**
     * @param $key
     *
     * @return mixed
     *
     * @throws \Exception
     */
    private function getFileHandleByKey($key)
    {
        $filename = $this->getFilenameByKey($key);
        if (!array_key_exists($filename, $this->fileHandles)) {
            $fp = fopen($filename, 'w+');
            if (!$fp) {
                throw new \Exception('生成缓存文件失败');
            }
            $this->fileHandles[$filename] = $fp;
        }

        return $this->fileHandles[$filename];
    }

    /**
     * @param $key
     *
     * @return string|string[]|null
     */
    private function convertKey($key)
    {
        return preg_replace('/^phpspreadsheet\./', '', $key);
    }
}
