<?php
/**
 * JBZoo Utils
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author    Denis Smetannikov <denis@jbzoo.com>
 */

namespace JBZoo\PHPUnit;

use JBZoo\Utils\Ser;

/**
 * Class SerTest
 * @package JBZoo\PHPUnit
 */
class TreeTest extends PHPUnit
{
    public function test() {

        $list = array(
            1 => array('id' => '1', 'pid' => 0, 'name' => '一级栏目一'),
            2 => array('id' => '2', 'pid' => 0, 'name' => '一级栏目二'),
            3 => array('id' => '3', 'pid' => 1, 'name' => '二级栏目一'),
            4 => array('id' => '4', 'pid' => 1, 'name' => '二级栏目二'),
            5 => array('id' => '5', 'pid' => 2, 'name' => '二级栏目三'),
            6 => array('id' => '6', 'pid' => 3, 'name' => '三级栏目一'),
            7 => array('id' => '7', 'pid' => 3, 'name' => '三级栏目二')
        );

        $tree = new \Utils\Tree($list);
        $html = "<select name='cat'>";
        $str = "<option value=\$$tree->param_id \$selected>\$spacer\$$tree->param_name</option>";
        $html .= $tree->get_tree(0, $str, $selected = 4);
        $html .= "</select>";
        echo $html;
    }
}