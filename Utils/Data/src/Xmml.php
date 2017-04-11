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

use Utils\XML;

/**
 * Class Xml
 */
class Xmml extends Data
{
    /**
     * takes raw XML as a parameter (a string) and returns an equivalent PHP data structure
     * @param string $string
     * @return mixed
     */
    protected function _decode($string) {
        $data = XML::parse($string);
        return $data;
    }

    /**
     * serializes any PHP data structure into XML
     * @param mixed $data
     * @return string
     */
    protected function _encode($data) {
        return XML::build($data);
    }
}
