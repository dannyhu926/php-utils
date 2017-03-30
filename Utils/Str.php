<?php

/**
 * Str.php. 字符串工具类
 *
 * @author    overtrue <i@overtrue.me>
 * @copyright 2015 overtrue <i@overtrue.me>
 *
 * @see      https://github.com/overtrue
 * @see      http://overtrue.me
 */

namespace Utils;

class Str
{
    /**
     * The cache of snake-cased words.
     *
     * @var array
     */
    protected static $snakeCache = [];

    /**
     * The cache of camel-cased words.
     *
     * @var array
     */
    protected static $camelCache = [];

    /**
     * The cache of studly-cased words.
     *
     * @var array
     */
    protected static $studlyCache = [];

    /**
     * Convert a value to camel case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function camel($value) {
        if (isset(static::$camelCache[$value])) {
            return static::$camelCache[$value];
        }

        return static::$camelCache[$value] = lcfirst(static::studly($value));
    }

    /**
     * 生成验证码
     *
     * @param int $length
     * @param string $type 验证码类型 FULL:数字和字母混合
     *
     * @return string
     *
     */
    function random($length = 6, $type = 'ENGLISH') {
        $result = '';

        $random_type = static::upper($type);
        $rulemap_str = "ABCDEFGHIJKLMNPQRSTUVWXYZ";
        $rulemap_num = "123456789";
        for ($i = 0; $i < $length; $i++) {
            switch ($random_type) {
                case 'ENGLISH':
                    $rand = mt_rand(0, (strlen($rulemap_str) - 1));
                    $result .= $rulemap_str[$rand];
                    break;
                case 'CHINESE':
                    $str[$i] = chr(mt_rand(176, 215)) . chr(mt_rand(161, 249));
                    $str[$i] = static::charsetEncode("GB2312", "UTF-8", $str[$i]); //imagettftext是utf-8的,所以先转换下
                    $result .= $str[$i];
                    break;
                case 'NUM':
                    $rand = mt_rand(0, (strlen($rulemap_num) - 1));
                    $result .= $rulemap_num[$rand];
                    break;
                case 'FULL':
                    $fullstr = $rulemap_str . $rulemap_num;
                    $rand = mt_rand(0, (strlen($fullstr) - 1));
                    $result .= $fullstr[$rand];
                    break;
            }
        }

        return $result;
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function upper($value) {
        return mb_strtoupper($value);
    }

    /**
     * Convert the given string to title case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function title($value) {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert a string to snake case.
     *
     * @param string $value
     * @param string $delimiter
     *
     * @return string
     */
    public static function snake($value, $delimiter = '_') {
        $key = $value . $delimiter;

        if (isset(static::$snakeCache[$key])) {
            return static::$snakeCache[$key];
        }

        if (!ctype_lower($value)) {
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/', '$1' . $delimiter, $value));
        }

        return static::$snakeCache[$key] = $value;
    }

    /**
     * Convert a value to studly caps case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function studly($value) {
        $key = $value;

        if (isset(static::$studlyCache[$key])) {
            return static::$studlyCache[$key];
        }

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return static::$studlyCache[$key] = str_replace(' ', '', $value);
    }

    public static function isJson($value) {
        return json_decode($value) !== null;
    }

    public static function json2Array($value) {
        return json_decode($value, true);
    }

    public static function urlSafeB64Encode($url) {
        return strtr(base64_encode($url), '+/=', '-_,');
    }

    public static function urlSafeB64Decode($url) {
        return base64_decode(strtr($url, '-_,', '+/='));
    }

    public static function stripTextarea($string) {
        return nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($string, ENT_QUOTES)));
    }

    /**
     * 实现多种字符编码方式
     * @param $input 需要编码的字符串
     * @param $_output_charset 输出的编码格式
     * @param $_input_charset 输入的编码格式
     * return 编码后的字符串
     */
    public static function charsetEncode($input, $_output_charset, $_input_charset) {
        $output = "";
        if (!isset($_output_charset)) $_output_charset = $_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else die("sorry, you have no libs support for charset change.");
        return $output;
    }

    /**
     * 实现多种字符解码方式
     * @param $input 需要解码的字符串
     * @param $_output_charset 输出的解码格式
     * @param $_input_charset 输入的解码格式
     * return 解码后的字符串
     */
    public static function charsetDecode($input, $_input_charset, $_output_charset) {
        $output = "";
        if (!isset($_input_charset)) $_input_charset = $_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else die("sorry, you have no libs support for charset changes.");
        return $output;
    }

    /**
     * 将未知编码的字符串转换为期望的编码（配置文件中设置的编码）
     * @param unknown $str
     * @param string $toEncoding
     * @return string
     */
    public static function convertStr($str, $toEncode = 'utf-8') {
        $charsetlist = array('ascii', 'gbk', 'gb2312', 'utf-8', 'big5');
        $strCode = mb_detect_encoding($str, $charsetlist);

        if (strtolower($strCode) != strtolower($toEncode)) {
            $str = iconv($strCode, $toEncode, $str);
        }

        return $str;
    }

    /**
     * 保留小数点后几位，并且不四舍五入
     * $bcscale 保留位数，默认2位
     */
    public static function numberFormat($number, $bcscale = 2) {
        $tmp_bcscale = $bcscale + 1;
        return sprintf("%.{$bcscale}f", substr(sprintf("%.{$tmp_bcscale}f", $number), 0, -1));
    }

    /**
     * 代码调试
     */
    public static function dd() {
        echo '<pre>';
        array_map(function ($x) {
            print_r($x);
        }, func_get_args());
        die;
    }

    public static function generateOrderNo() {
        return date('Ymd') . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
    }
}
