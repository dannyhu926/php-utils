<?php
/**
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author   hudy <469671292@163.com>
 */

namespace Utils\Data;

# get file from url https://github.com/symfony/yaml/blob/master/Yaml.php
use Symfony\Component\Yaml\Yaml;

/**
 * Class Yml
 * @package JBZoo\Data
 */
class Yml extends Data
{
    /**
     * Utility Method to serialize the given data
     * @param mixed $data The data to serialize
     * @return string The serialized data
     */
    protected function _encode($data)
    {
        return Yaml::dump($data);
    }

    /**
     * Utility Method to unserialize the given data
     * @param string $string
     * @return mixed
     */
    protected function _decode($string)
    {
        return Yaml::parse($string);
    }
}
