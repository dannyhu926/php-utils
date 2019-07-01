<?php
/**
 * Class DES     PHP7 解决 java对应的 AES/ECB/PKCS5Padding 算法
 *
 */


class DES
{
    /**
     * @param string $data 需要加密的字符串   
     * @param string $key  密钥   
     * @return string   
     */
    public static function encrypt($data, $key)
    {
        $data = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return base64_encode($data);
    }

    /**
     * @param string $data 需要解密的字符串   
     * @param string $key  密钥   
     * @return string   
     */
    public static function decrypt($data, $key)
    {
        $encrypted = base64_decode($data);
        return openssl_decrypt($encrypted, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }
}


