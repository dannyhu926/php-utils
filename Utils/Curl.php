<?php
/**
 * Curl.php Curl请求类
 *
 * @author   hudy <469671292@163.com>
 *
 * from https://github.com/dsyph3r/curl-php/blob/master/lib/Network/Curl/Curl.php
 */

namespace Utils;

class Curl
{
    /**
     * Constants for available HTTP methods.
     */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const PATCH = 'PATCH';
    const DELETE = 'DELETE';

    /**
     * CURL句柄.
     *
     * @var resource handle
     */
    protected $curl;

    /**
     * Create the cURL resource.
     */
    public function __construct() {
        $this->curl = curl_init();
    }

    /**
     * Clean up the cURL handle.
     */
    public function __destruct() {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }

    /**
     * Get the cURL handle.
     *
     * @return resource cURL handle
     */
    public function getCurl() {
        return $this->curl;
    }

    /**
     * Make a HTTP GET request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return array
     */
    public function get($url, $params = array(), $options = array()) {
        return $this->request($url, self::GET, $params, $options);
    }

    /**
     * Make a HTTP POST request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return array
     */
    public function post($url, $params = array(), $options = array()) {
        return $this->request($url, self::POST, $params, $options);
    }

    /**
     * Make a HTTP PUT request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return array
     */
    public function put($url, $params = array(), $options = array()) {
        return $this->request($url, self::PUT, $params, $options);
    }

    /**
     * Make a HTTP PATCH request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return array
     */
    public function patch($url, $params = array(), $options = array()) {
        return $this->request($url, self::PATCH, $params, $options);
    }

    /**
     * Make a HTTP DELETE request.
     *
     * @param string $url
     * @param array $params
     * @param array $options
     *
     * @return array
     */
    public function delete($url, $params = array(), $options = array()) {
        return $this->request($url, self::DELETE, $params, $options);
    }

    protected function hasPostData($method) {
        return in_array(strtoupper($method), [self::PUT, self::POST, self::PATCH]);
    }

    /**
     * 设置curl选项
     * @param resource $ch curl句柄
     * @param string $method 请求方法
     * @param string $data post数据
     */
    protected function setCurlOpt(&$ch, $url, $method, $params, $options) {
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (isset($options['timeout'])) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $url);

        //使用证书情况
        if (isset($options['sslcert_path']) && isset($options['sslkey_path'])) {
            if (!file_exists($options['sslcert_path']) || !file_exists($options['sslkey_path'])) {
                throw new \Exception('Certfile is not correct');
            }
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格校验
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $options['sslcert_path']);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $options['sslkey_path']);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        // Check for files
        if ($this->hasPostData($method)) {
            if (isset($options['files']) && count($options['files'])) {
                foreach ($options['files'] as $index => $file) {
                    $params[$index] = $this->createCurlFile($file);
                }

                version_compare(PHP_VERSION, '5.5', '<') || curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);

                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } else {
                if (isset($options['json'])) {
                    $params = JSON::encode($params);
                    $options['headers'][] = 'content-type:application/json';
                }

                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, false);
        }

        // Check for custom headers
        if (isset($options['headers']) && count($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Check for basic auth
        if (isset($options['auth']['type']) && 'basic' === $options['auth']['type']) {
            curl_setopt($ch, CURLOPT_USERPWD, $options['auth']['username'] . ':' . $options['auth']['password']);
        }
    }

    /**
     * Make a HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @param array $options
     *
     * @return array
     */
    protected function request($url, $method = self::GET, $params = array(), $options = array()) {
        if (!$this->hasPostData($method)) {
            $url .= (stripos($url, '?') ? '&' : '?') . http_build_query($params);
        }

        $this->setCurlOpt($this->curl, $url, $method, $params, $options);
        $response = $this->doCurl();

        // Separate headers and body
        $headerSize = $response['curl_info']['header_size'];
        $header = substr($response['response'], 0, $headerSize);
        $body = substr($response['response'], $headerSize);

        $results = array(
            'curl_info' => $response['curl_info'],
            'content_type' => $response['curl_info']['content_type'],
            'status' => $response['curl_info']['http_code'],
            'headers' => $this->splitHeaders($header),
            'data' => $body,
        );

        return $results;
    }

    /**
     * 同时发出多个请求
     * @param array $param 参数array(['method'=>'', 'url'=>'', 'params'=>'', 'options'=>''],... )
     * @return array
     */
    public function multiRequest($param) {
        if (count($param) == 0) {
            return null;
        }
        $mh = curl_multi_init();
        $results = $chmap = [];
        foreach ($param as $k => $row) {
            $results[$k] = null;
            if (!array_key_exists('url', $row) || !array_key_exists('method', $row)) {
                continue;
            }
            $chmap[$k] = curl_init();
            $this->setCurlOpt($chmap[$k], $row['url'], $row['method'], $row['params'], $row['options']);
            curl_multi_add_handle($mh, $chmap[$k]);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        while ($active && $mrc == CURLM_OK) {
            while (curl_multi_exec($mh, $active) === CURLM_CALL_MULTI_PERFORM) ;
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
        foreach ($chmap as $k => $ch) {
            $response = curl_multi_getcontent($ch);
            $results[$k] = $response;
        }
        foreach ($chmap as $k => $ch) {
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $results;
    }

    /**
     * make cURL file.
     *
     * @param string $filename
     *
     * @return \CURLFile|string
     */
    protected function createCurlFile($filename) {
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }

        return "@$filename;filename=" . basename($filename);
    }

    /**
     * Split the HTTP headers.
     *
     * @param string $rawHeaders
     *
     * @return array
     */
    protected function splitHeaders($rawHeaders) {
        $headers = array();

        $lines = explode("\n", trim($rawHeaders));
        $headers['HTTP'] = array_shift($lines);

        foreach ($lines as $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                $headers[$h[0]] = trim($h[1]);
            }
        }

        return $headers;
    }

    /**
     * Perform the Curl request.
     *
     * @return array
     */
    protected function doCurl() {
        $response = curl_exec($this->curl);

        if (curl_errno($this->curl)) {
            throw new \Exception(curl_error($this->curl), 1);
        }

        $curlInfo = curl_getinfo($this->curl);

        $results = array(
            'curl_info' => $curlInfo,
            'response' => $response,
        );

        return $results;
    }
}