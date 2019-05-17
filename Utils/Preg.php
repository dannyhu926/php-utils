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
     * @param  int $index
     * @return array
     */
    public static function getStringNumber($string, $index = -1, $length = 6)
    {
        $arrMatches = [];

        if (!empty($string)) {
            $rule = sprintf("/([0-9]{%s})/", $length);
            preg_match_all($rule, $string, $arrMatches);
            if ($arrMatches) {
                $arrMatches = $arrMatches['0'];
                if (isset($arrMatches[$index])) {
                    $arrMatches = $arrMatches[$index];
                }
            }
        }

        return $arrMatches;
    }

    /**
     * 得到字符串中括号里面的内容
     * @param $string
     * @param string $type curves：小括号 brackets：中括号 brace：大括号 square:方括号
     * @param int $index
     * @return array|mixed
     */
    public static function getBracketsString($string, $type = "curves", $index = -1)
    {
        $arrMatches = [];

        if (!empty($string)) {
            switch ($type) {
                case 'curves':
                    $rule = "/(\(|（)(.*?)(\)|）)/";
                    break;
                case 'brackets':
                    $rule = "/(《|<)(.*?)(>|》)/";
                    break;
                case 'brace':
                    $rule = "/(\{)(.*?)(\})/";
                    break;
                case 'square':
                    $rule = "/(\[|【)(.*?)(\]|】)/";
                    break;
            }
            preg_match_all($rule, $string, $arrMatches);
            if ($arrMatches) {
                $arrMatches = $arrMatches['2'];
                if (isset($arrMatches[$index])) {
                    $arrMatches = $arrMatches[$index];
                }
            }
        }

        return $arrMatches;
    }

    /**
     * 获得<title></title>中的数据
     * @param  $string
     * @param  $tag
     * @param  int $index
     * @return array
     */
    public static function getTagData($string, $tag, $index = -1)
    {
        $arrMatches = [];

        if (!empty($string)) {
            preg_match_all("/<($tag.*?)>(.*?)<(\/$tag.*?)>/si", $string, $arrMatches);
            if ($arrMatches) {
                $arrMatches = $arrMatches['2'];
                if (isset($arrMatches[$index])) {
                    $arrMatches = $arrMatches[$index];
                }
            }
        }

        return $arrMatches;
    }

    /**
     * 相对路径转化成绝对路径
     * @param $content
     * @param $feed_url http://www.test.com
     * @param array $tags 转换的标签
     * @return null|string|string[]
     */
    public static function relative2Absolute($content, $feed_url, $tags = [ 'href', 'src' ])
    {
        preg_match('/(http|https|ftp):\/\//', $feed_url, $protocol);
        $server_url = preg_replace("/(http|https|ftp|news):\/\//", "", $feed_url);
        $server_url = preg_replace("/\/.*/", "", $server_url);

        if ($server_url == '') {
            return $content;
        }

        if (isset($protocol[0])) {
            foreach ($tags as $tag) {
                $content = preg_replace('/'.$tag.'="\//', $tag.'="'.$protocol[0].$server_url.'/', $content);
                $content = preg_replace("/$tag='\//", $tag.'=\''.$protocol[0].$server_url.'/', $content);
            }
        }
        return $content;
    }

    /**
     * 取得所有链接
     * @param $content
     * @return array
     */
    public static function getAllUrl($content)
    {
        preg_match_all('/<a\s+href=["|\']?([^>"\' ]+)["|\']?\s*[^>]*>([^>]+)<\/a>/i', $content, $arr);
        return array( 'name' => $arr[2], 'url' => $arr[1] );
    }
}
