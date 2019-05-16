<?php
/**
 *  正则匹配
 *
 * @package Utils
 * @link    https://github.com/JBZoo/Utils
 * @author  hudy <469671292@163.com>
 */

namespace Utils;

class Preg
{
    /**
     * 得到字符串中指定长度的数字
     *
     * @param  string $string
     * @param  int $length
     * @return array
     */
    public static function getStringNumber($string, $length = 6)
    {
        $arrMatches = [];

        if (!empty($string)) {
            $rule = sprintf("/([0-9]{%s})/", $length);
            preg_match_all($rule, $string, $arrMatches);
            if ($arrMatches) {
                $arrMatches = $arrMatches['0'];
            }
        }

        return $arrMatches;
    }

    /**
     * 得到字符串中【】里面的内容
     *
     * @param string $string
     * return array
     */
    public static function getBracketsString($string)
    {
        $arrMatches = [];

        if (!empty($string)) {
            preg_match_all("/(?<=【)[^】]+/", $string, $arrMatches);
            if ($arrMatches) {
                $arrMatches = $arrMatches['0'];
            }
        }

        return $arrMatches;
    }
}
