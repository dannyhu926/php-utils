<?php
/**
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      http://hudeyong926.iteye.com/blog/836048
 * @author   hudy <469671292@163.com>
 */

namespace Utils\Data;

# * @link get file from url  http://hudeyong926.iteye.com/blog/836048
#use Symfony\Component\Yaml\XML;

/**
 * Class Xml
 */
class Xml extends Data
{
    /**
     * takes raw XML as a parameter (a string) and returns an equivalent PHP data structure
     * @param string $string
     * @return mixed
     */
    protected function _decode($string) {
        $xml_parser = & new XML();
        $data = & $xml_parser->parse($string);
        $xml_parser->destruct();
        return $data;
    }

    /**
     * @param mixed $data
     * @return string
     */
    protected function _encode($data) {
        return $this->_render($data);
    }

    /**
     * serializes any PHP data structure into XML
     * @param array $data
     * @param array $parent
     * @return string
     */
    protected function _render($data, $level = 0, $prior_key = NULL) {
        if ($level == 0) {
            ob_start();
            echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
        }
        while (list($key, $value) = each($data))
            if (!strpos($key, ' attr')) #if it's not an attribute
                #we don't treat attributes by themselves, so for an empty element
                # that has attributes you still need to set the element to NULL

                if (is_array($value) and array_key_exists(0, $value)) {
                    $this->_render($value, $level, $key);
                } else {
                    $tag = $prior_key ? $prior_key : $key;
                    echo str_repeat("\t", $level), '<', $tag;
                    if (array_key_exists("$key attr", $data)) { #if there's an attribute for this element
                        while (list($attr_name, $attr_value) = each($data["$key attr"]))
                            echo ' ', $attr_name, '="', htmlspecialchars($attr_value), '"';
                        reset($data["$key attr"]);
                    }

                    if (is_null($value)) echo " />\n";
                    elseif (!is_array($value)) echo '>', htmlspecialchars($value), "</$tag>\n";
                    else echo ">\n", $this->_render($value, $level + 1), str_repeat("\t", $level), "</$tag>\n";
                }
        reset($data);
        if ($level == 0) {
            $str = & ob_get_contents();
            ob_end_clean();
            return $str;
        }
    }
}
