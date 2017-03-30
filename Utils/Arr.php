<?php

/**
 * Arr.php. 数组工具类
 *
 * @author    overtrue <i@overtrue.me>
 *
 * @see      https://github.com/overtrue
 * @see      https://github.com/laravel/framework/blob/4.2/src/Illuminate/Support/Arr.php
 */

namespace Utils;

use Closure;

/**
 * Array helper from Illuminate\Support\Arr.
 */
class Arr
{
    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     * 如果不存在，将一个元素添加到数组中。
     * @param array $array
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    public static function add($array, $key, $value) {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Build a new array using a callback.
     * 使用回调函数生成新数组。
     *
     * @param array $array
     * @param \Closure $callback
     *
     * @return array
     */
    public static function build($array, Closure $callback) {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);
            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     * 将数组分成两个数组。一个是key，另一个是value。
     * @param array $array
     *
     * @return array
     */
    public static function divide($array) {
        return [
            array_keys($array),
            array_values($array),
        ];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     * 数组索引key加前缀
     * @param array $array
     * @param string $prepend
     *
     * @return array
     */
    public static function dot($array, $prepend = '') {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of items.
     * 获取指定数组，除了指定的数组项。
     * @param array $array
     * @param array|string $keys
     *
     * @return array
     */
    public static function except($array, $keys) {
        return array_diff_key($array, array_flip((array)$keys));
    }

    /**
     * Fetch a flattened array of a nested array element.
     *
     * @param array $array
     * @param string $key
     *
     * @return array
     */
    public static function fetch($array, $key) {
        $results = [];

        foreach (explode('.', $key) as $segment) {
            $results = [];
            foreach ($array as $value) {
                $value = (array)$value;
                $results[] = $value[$segment];
            }
            $array = array_values($results);
        }

        return array_values($results);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array $array
     * @param \Closure $callback
     * @param mixed $default
     *
     * @return mixed
     */
    public static function first($array, $callback, $default = null) {
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array $array
     * @param \Closure $callback
     * @param mixed $default
     *
     * @return mixed
     */
    public static function last($array, $callback, $default = null) {
        return static::first(array_reverse($array), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     * 将多维数组变平为一维。
     * @param array $array
     *
     * @return array
     */
    public static function flatten($array) {
        $return = [];
        array_walk_recursive(
            $array,
            function ($x) use (&$return) {
                $return[] = $x;
            }
        );

        return $return;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param array $array
     * @param array|string $keys
     */
    public static function forget(&$array, $keys) {
        $original = & $array;

        foreach ((array)$keys as $key) {
            $parts = explode('.', $key);
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = & $array[$part];
                }
            }
            unset($array[array_shift($parts)]);
            // clean up after each pass
            $array = & $original;
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null) {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param array $array
     * @param array|string $keys
     *
     * @return array
     */
    public static function only($array, $keys) {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param array $array
     * @param string $value
     * @param string $key
     *
     * @return array
     */
    public static function pluck($array, $value, $key = null) {
        $results = [];

        foreach ($array as $item) {
            $itemValue = is_object($item) ? $item->{$value} : $item[$value];
            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = is_object($item) ? $item->{$key} : $item[$key];
                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null) {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);

        return $value;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    public static function set(&$array, $key, $value) {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = & $array[$key];
        }
        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Sort the array using the given Closure.
     *
     * @param array $array
     * @param \Closure $callback
     *
     * @return array
     */
    public static function sort($array, Closure $callback) {
        $results = [];

        foreach ($array as $key => $value) {
            $results[$key] = $callback($value);
        }

        return $results;
    }

    /**
     * Filter the array using the given Closure.
     *
     * @param array $array
     * @param \Closure $callback
     *
     * @return array
     */
    public static function where($array, Closure $callback) {
        $filtered = [];

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * 对数据进行编码转换
     * @param array /string $array 数组
     * @param string $input 需要转换的编码
     * @param string $output 转换后的编码
     */
    public static function array_iconv($array, $input = 'gbk', $output = 'utf-8') {
        if (!is_array($array)) {
            return iconv($input, $output, $array);
        } else {
            foreach ($array as $key => $val) {
                if (is_array($val)) {
                    $array[$key] = static::array_iconv($val, $input, $output);
                } else {
                    $array[$key] = iconv($input, $output, $val);
                }
            }
            return $array;
        }
    }

    /**
     * 在指定的位置插入元素.
     *
     * @param array $array
     * @param \Closure $callback
     *
     * @return array
     */
    public static function insert($array, $value, $index, $index_key = '') {
        $fore = array_splice($array, 0, $index);
        if ($index_key) {
            $fore[$index_key] = $value;
        } else {
            $fore[] = $value;
        }
        $ret = array_merge($fore, $array);
        return $ret;
    }
}
