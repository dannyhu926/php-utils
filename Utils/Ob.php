<?php
/**
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author   hudy <469671292@163.com>
 */

namespace Utils;

/**
 * Class Ob
 * @package JBZoo\Utils
 */
class Ob
{
    /**
     * Clean all ob_* buffers
     */
    public static function clean()
    {
        while (@ob_end_clean()) {
            // noop
        }
    }
}
