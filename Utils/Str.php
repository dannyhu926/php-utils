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
     * Default charset is UTF-8
     * @var string
     */
    public static $encoding = 'UTF-8';

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
     * Determine if a given string contains a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function contains($haystack, $needles) {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles) {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && substr($haystack, 0, strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string $haystack
     * @param  string|array $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles) {
        foreach ((array)$needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string)$needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string $value
     * @param  string $cap
     * @return string
     */
    public static function finish($value, $cap) {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string $pattern
     * @param  string $value
     * @return bool
     */
    public static function is($pattern, $value) {
        if ($pattern == $value) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool)preg_match('#^' . $pattern . '\z#u', $value);
    }

    /**
     * Generate Readable random string 生成可读的随机字符串
     *
     * @param int $length
     * @return string
     */
    public static function randomString($length = 10, $isReadable = true) {
        $result = '';

        $vocal = array('a', 'e', 'i', 'o', 'u', '0');
        $conso = array('b', 'c', 'd', 'f', 'g',
            'h', 'j', 'k', 'l', 'm', 'n', 'p',
            'r', 's', 't', 'v', 'w', 'x', 'y', 'z',
            '1', '2', '3', '4', '5', '6', '7', '8', '9',
        );

        $max = $length / 2;

        for ($pos = 1; $pos <= $max; $pos++) {
            $result .= $conso[mt_rand(0, count($conso) - 1)];
            $result .= $vocal[mt_rand(0, count($vocal) - 1)];
        }

        return $result;
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
                    $str[$i] = static::charsetEncode($str[$i], "GB2312", "UTF-8"); //imagettftext是utf-8的,所以先转换下
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
     * Convert the given string to lower-case.
     *
     * @param  string $value
     * @return string
     */
    public static function lower($value) {
        return mb_strtolower($value, 'UTF-8');
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
            $value = preg_replace('/\s+/u', '', $value);
            $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
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
            $str = static::charsetEncode($str, $strCode, $toEncode);
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
        die(1);
    }

    public static function generateOrderNo($prefix = '') {
        return $prefix . date('Ymd') . static::zeroPad(mt_rand(1, 9999999), 7);
    }

    /**
     *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     *
     * @access  public
     * @param   string $str 待转换字串
     *
     * @return  string $str 处理后字串
     */
    public static function full2semiangle($str) {
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
            'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
            'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
            'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
            'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
            'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
            '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
            '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
            '》' => '>',
            '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
            '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
            '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
            '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
            '　' => ' ', '＄' => '$', '＠' => '@', '＃' => '#', '＾' => '^', '＆' => '&', '＊' => '*');

        return strtr($str, $arr);
    }

    /**
     * 字符截取 支持UTF8/GBK 英文数字1个字节 gbk2个字节，utf-8 3个字节
     * @param $string
     * @param $length
     * @param $dot
     */
    public static function strCut($string, $length, $charset = 'utf-8', $dot = '...') {
        if (!in_array(strtoupper($charset), array('UTF-8', 'GBK'))) {
            $charset = 'utf-8';
        }
        $strlen = strlen($string);
        if ($strlen <= $length) return $string;
        $string = str_replace(array(' ', '&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array('∵', ' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
        $strcut = '';
        if (strtolower($charset) == 'utf-8') {
            $length = intval($length - strlen($dot) - $length / 3);
            $n = $tn = $noc = 0;
            while ($n < strlen($string)) {
                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t <= 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }
                if ($noc >= $length) {
                    break;
                }
            }
            if ($noc > $length) {
                $n -= $tn;
            }
            $strcut = substr($string, 0, $n);
            $strcut = str_replace(array('∵', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), array(' ', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), $strcut);
        } else {
            $dotlen = strlen($dot);
            $maxi = $length - $dotlen - 1;
            $current_str = '';
            $search_arr = array('&', ' ', '"', "'", '“', '”', '—', '<', '>', '·', '…', '∵');
            $replace_arr = array('&amp;', '&nbsp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;', ' ');
            $search_flip = array_flip($search_arr);
            for ($i = 0; $i < $maxi; $i++) {
                $current_str = ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
                if (in_array($current_str, $search_arr)) {
                    $key = $search_flip[$current_str];
                    $current_str = str_replace($search_arr[$key], $replace_arr[$key], $current_str);
                }
                $strcut .= $current_str;
            }
        }
        return $strcut . $dot;
    }


    /**
     * Strip all witespaces from the given string.
     *
     * @param  string $string The string to strip
     * @return string
     */
    public static function stripSpace($string) {
        return preg_replace('/\s+/', '', $string);
    }

    /**
     * Parse text by lines
     *
     * @param string $text
     * @param bool $toAssoc
     * @return array
     */
    public static function parseLines($text, $toAssoc = true) {
        $text = htmlspecialchars_decode($text);
        $text = self::clean($text, false, false);

        $text = str_replace(array("\n", "\r", "\r\n", PHP_EOL), "\n", $text);
        $lines = explode("\n", $text);

        $result = array();
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if ($toAssoc) {
                    $result[$line] = $line;
                } else {
                    $result[] = $line;
                }
            }
        }

        return $result;
    }

    /**
     * Make string safe
     * - Remove UTF-8 chars
     * - Remove all tags
     * - Trim
     * - Addslashes (opt)
     * - To lower (opt)
     *
     * @param string $string
     * @param bool $toLower
     * @param bool $addslashes
     * @return string
     */
    public static function clean($string, $toLower = false, $addslashes = false) {
        $string = Slug::removeAccents($string);
        $string = strip_tags($string);
        $string = trim($string);

        if ($addslashes) {
            $string = addslashes($string);
        }

        if ($toLower) {
            $string = self::low($string);
        }

        return $string;
    }

    /**
     * Convert >, <, ', " and & to html entities, but preserves entities that are already encoded.
     *
     * @param string $string The text to be converted
     * @param bool $encodedEntities
     * @return string
     */
    public static function htmlEnt($string, $encodedEntities = false) {
        if ($encodedEntities) {
            // @codeCoverageIgnoreStart
            if (defined('HHVM_VERSION')) {
                $transTable = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
            } else {
                /** @noinspection PhpMethodParametersCountMismatchInspection */
                $transTable = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES, self::$encoding);
            }
            // @codeCoverageIgnoreEnd

            $transTable[chr(38)] = '&';

            $regExp = '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/';

            return preg_replace($regExp, '&amp;', strtr($string, $transTable));
        }

        return htmlentities($string, ENT_QUOTES, self::$encoding);
    }

    /**
     * Pads a given string with zeroes on the left.
     *
     * @param  int $number The number to pad
     * @param  int $length The total length of the desired string
     * @return string
     */
    public static function zeroPad($number, $length) {
        return str_pad($number, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Check if a given string matches a given pattern.
     *
     * @param  string $pattern Parttern of string exptected
     * @param  string $string String that need to be matched
     * @param  bool $caseSensitive
     * @return bool
     */
    public static function like($pattern, $string, $caseSensitive = true) {
        if ($pattern == $string) {
            return true;
        }

        // Preg flags
        $flags = $caseSensitive ? '' : 'i';

        // Escape any regex special characters
        $pattern = preg_quote($pattern, '#');

        // Unescape * which is our wildcard character and change it to .*
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool)preg_match('#^' . $pattern . '$#' . $flags, $string);
    }

    /**
     * Converts any accent characters to their equivalent normal characters
     *
     * @param string $text
     * @param bool $isCache
     * @return string
     */
    public static function slug($text = '', $isCache = false) {
        static $cache = array();

        if (!$isCache) {
            return Slug::filter($text);

        } elseif (!array_key_exists($text, $cache)) { // Not Arr::key() for performance
            $cache[$text] = Slug::filter($text);
        }

        return $cache[$text];
    }

    /**
     * Check is mbstring loaded
     *
     * @return bool
     */
    public static function isMBString() {
        static $isLoaded;
        if (null === $isLoaded) {
            $isLoaded = extension_loaded('mbstring');
            if ($isLoaded) {
                mb_internal_encoding(self::$encoding);
            }
        }
        return $isLoaded;
    }

    /**
     * 获取字符串长度，主要针对非纯英文字符串
     *
     * @static
     * @param $string
     * @param string $charset
     * @return int
     */
    public static function len($string, $charset = 'UTF-8') {
        $len = strlen($string);
        $i = $count = 0;
        $charset = strtolower(substr($charset, 0, 3));
        while ($i < $len) {
            if (ord($string[$i]) <= 129)
                $i++;
            else
                switch ($charset) {
                    case 'UTF-8':
                        $i += 3;
                        break;
                    default:
                        $i += 2;
                        break;
                }
            $count++;
        }
        return $count;
    }

    /**
     * Trim whitespaces and other special chars
     *
     * @param string $value
     * @param bool $extendMode
     * @return string
     */
    public static function trim($value, $extendMode = false) {
        $result = (string)trim($value);

        if ($extendMode) {
            $result = trim($result, chr(0xE3) . chr(0x80) . chr(0x80));
            $result = trim($result, chr(0xC2) . chr(0xA0));
            $result = trim($result);
        }

        return $result;
    }

    /**
     * Escape string before save it as xml content
     *
     * @param $string
     * @return mixed
     */
    public static function escXml($string) {
        $string = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);

        $string = str_replace(
            array("&", "<", ">", '"', "'"),
            array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"),
            $string
        );

        return $string;
    }

    /**
     * Escape UTF-8 strings
     *
     * @param string $string
     * @return string
     */
    public static function esc($string) {
        return htmlspecialchars($string, ENT_NOQUOTES, self::$encoding);
    }

    /**
     * Generates a universally unique identifier (UUID v4) according to RFC 4122
     * Version 4 UUIDs are pseudo-random!
     *
     * Returns Version 4 UUID format: xxxxxxxx-xxxx-4xxx-Yxxx-xxxxxxxxxxxx where x is
     * any random hex digit and Y is a random choice from 8, 9, a, or b.
     *
     * @see http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
     *
     * @return string
     */
    public static function uuid() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Get class name without namespace
     *
     * @param mixed $object
     * @param bool $toLower
     * @return mixed|string
     */
    public static function getClassName($object, $toLower = false) {
        if (is_object($object)) {
            $className = get_class($object);
        } else {
            $className = $object;
        }

        $result = $className;
        if (strpos($className, '\\') !== false) {
            $className = explode('\\', $className);
            reset($className);
            $result = end($className);
        }

        if ($toLower) {
            $result = strtolower($result);
        }

        return $result;
    }

    /**
     * Increments a trailing number in a string.
     * Used to easily create distinct labels when copying objects. The method has the following styles:
     *  - default: "Label" becomes "Label (2)"
     *  - dash:    "Label" becomes "Label-2"
     *
     * @param   string $string The source string.
     * @param   string $style The the style (default|dash).
     * @param   integer $next If supplied, this number is used for the copy, otherwise it is the 'next' number.
     * @return  string
     */
    public static function inc($string, $style = 'default', $next = 0) {
        $styles = array(
            'dash' => array(
                '#-(\d+)$#', '-%d'
            ),
            'default' => array(
                array('#\((\d+)\)$#', '#\(\d+\)$#'),
                array(' (%d)', '(%d)'),
            ),
        );

        $styleSpec = isset($styles[$style]) ? $styles[$style] : $styles['default'];

        // Regular expression search and replace patterns.
        if (is_array($styleSpec[0])) {
            $rxSearch = $styleSpec[0][0];
            $rxReplace = $styleSpec[0][1];
        } else {
            $rxSearch = $rxReplace = $styleSpec[0];
        }

        // New and old (existing) sprintf formats.
        if (is_array($styleSpec[1])) {
            $newFormat = $styleSpec[1][0];
            $oldFormat = $styleSpec[1][1];
        } else {
            $newFormat = $oldFormat = $styleSpec[1];
        }

        // Check if we are incrementing an existing pattern, or appending a new one.
        if (preg_match($rxSearch, $string, $matches)) {
            $next = empty($next) ? ($matches[1] + 1) : $next;
            $string = preg_replace($rxReplace, sprintf($oldFormat, $next), $string);
        } else {
            $next = empty($next) ? 2 : $next;
            $string .= sprintf($newFormat, $next);
        }

        return $string;
    }


    /**
     * Splits a string of multiple queries into an array of individual queries.
     * Single line or line end comments and multi line comments are stripped off.
     *
     * @param   string $sql Input SQL string with which to split into individual queries.
     * @return  array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function splitSql($sql) {
        $start = 0;
        $open = false;
        $comment = false;
        $endString = '';
        $end = strlen($sql);
        $queries = array();
        $query = '';

        for ($i = 0; $i < $end; $i++) {
            $current = substr($sql, $i, 1);
            $current2 = substr($sql, $i, 2);
            $current3 = substr($sql, $i, 3);
            $lenEndString = strlen($endString);
            $testEnd = substr($sql, $i, $lenEndString);

            if ($current == '"' || $current == "'" || $current2 == '--'
                || ($current2 == '/*' && $current3 != '/*!' && $current3 != '/*+')
                || ($current == '#' && $current3 != '#__')
                || ($comment && $testEnd == $endString)
            ) {
                // Check if quoted with previous backslash
                $num = 2;

                while (substr($sql, $i - $num + 1, 1) == '\\' && $num < $i) {
                    $num++;
                }

                // Not quoted
                if ($num % 2 == 0) {
                    if ($open) {
                        if ($testEnd == $endString) {
                            if ($comment) {
                                $comment = false;
                                if ($lenEndString > 1) {
                                    $i += ($lenEndString - 1);
                                    $current = substr($sql, $i, 1);
                                }
                                $start = $i + 1;
                            }
                            $open = false;
                            $endString = '';
                        }
                    } else {
                        $open = true;
                        if ($current2 == '--') {
                            $endString = "\n";
                            $comment = true;
                        } elseif ($current2 == '/*') {
                            $endString = '*/';
                            $comment = true;
                        } elseif ($current == '#') {
                            $endString = "\n";
                            $comment = true;
                        } else {
                            $endString = $current;
                        }
                        if ($comment && $start < $i) {
                            $query = $query . substr($sql, $start, ($i - $start));
                        }
                    }
                }
            }

            if ($comment) {
                $start = $i + 1;
            }

            if (($current == ';' && !$open) || $i == $end - 1) {
                if ($start <= $i) {
                    $query = $query . substr($sql, $start, ($i - $start + 1));
                }
                $query = trim($query);

                if ($query) {
                    if (($i == $end - 1) && ($current != ';')) {
                        $query = $query . ';';
                    }
                    $queries[] = $query;
                }

                $query = '';
                $start = $i + 1;
            }
        }

        return $queries;
    }
}
