<?php

/**
 * Arr.php. 数组工具类
 *
 * @author   hudy <469671292@163.com>
 *
 * @see      https://github.com/overtrue
 * @see      https://github.com/laravel/framework/blob/4.2/src/Illuminate/Support/Arr.php
 */

namespace Utils;

use Closure;
use ArrayAccess;

/**
 * Array helper from Illuminate\Support\Arr.
 */
class Arr
{
    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed $value
     * @return bool
     */
    public static function accessible($value) {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *  将一个指定键的元素添加进数组，如果数组中已有该键，则不添加。
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
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     * 函数将多维数组转为一维数组，该数组不需要规则的结构。所有的键用'.'分割。
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
     *  函数移除指定键的元素（数组的第一维），第二个参数包含所有要移除键的数组，并返回新数组
     * @param array $array
     * @param array|string $keys
     *
     * @return array
     */
    public static function except($array, $keys) {
        return array_diff_key($array, array_flip((array)$keys));
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array $array
     * @param  string|int $key
     * @return bool
     */
    public static function exists($array, $key) {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * Fetch a flattened array of a nested array element.
     * 获取多维数组的最终值，参数为 第一维的键.第二维的键.第三维的键.... 的形式
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
     * @param  array $array
     * @param  callable|null $callback
     * @param  mixed $default
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null) {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }
            foreach ($array as $item) {
                return $item;
            }
        }
        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                return $value;
            }
        }
        return value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array $array
     * @param  callable|null $callback
     * @param  mixed $default
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null) {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }
        return static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     * 获取多维数组的最终值，该数组不需要规则的结构。并丢掉键
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
     * 移除数组内指定的元素，通过 键.键.键 的形式来寻找指定要移除的元素
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
     * 获取数组内指定的元素，通过 键.键.键 的形式来寻找指定的元素
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
     * 返回数组内指定键(仅限第一维的键)的的元素，参数为将获取的数组元素键的数组。
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
     * 返回数组内指定键的值，并丢掉键，只能指定一个键。
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
     * 从原数组中删除指定键的元素，并返回被删除的元素的值
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
     * 为一个指定键的元素设置值，通过 键.键.键 的形式来寻找将被设置或重新赋值的元素。
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
     * 函数对数组里的第一维元素进行自定义排序，并保持第一维数组键。
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
     * Recursively sort an array by keys and values.
     *
     * @param  array $array
     * @return array
     */
    public static function sortRecursive($array) {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::sortRecursive($value);
            }
        }
        if (static::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }
        return $array;
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array $array
     * @param  callable $callback
     * @return array
     */
    public static function where($array, callable $callback) {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
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
     * Push an item onto the beginning of an array.
     *
     * @param  array $array
     * @param  mixed $value
     * @param  mixed $key
     * @return array
     */
    public static function prepend($array, $value, $key = null) {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }
        return $array;
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


    /**
     * Remove the duplicates from an array.
     *
     * @param array $array
     * @param bool $keepKeys
     * @return array
     */
    public static function unique($array, $keepKeys = false) {
        if ($keepKeys) {
            $array = array_unique($array);
        } else {
            $array = array_keys(array_flip($array));
        }

        return $array;
    }

    /**
     * Check is value exists in the array
     *
     * @param string $value
     * @param mixed $array
     * @param bool $returnKey
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public static function in($value, array $array, $returnKey = false) {
        $inArray = in_array($value, $array, true);

        if ($returnKey) {
            if ($inArray) {
                return array_search($value, $array, true);
            }

            return null;
        }

        return $inArray;
    }

    /**
     * Searches for a given value in an array of arrays, objects and scalar values. You can optionally specify
     * a field of the nested arrays and objects to search in.
     *
     * @param  array $array The array to search
     * @param  mixed $search The value to search for
     * @param  bool $field The field to search in, if not specified all fields will be searched
     * @return boolean|mixed  False on failure or the array key on success
     */
    public static function search(array $array, $search, $field = false) {
        // *grumbles* stupid PHP type system
        $search = (string)$search;
        foreach ($array as $key => $elem) {
            // *grumbles* stupid PHP type system

            $key = (string)$key;

            if ($field) {
                if (is_object($elem) && $elem->{$field} === $search) {
                    return $key;

                } elseif (is_array($elem) && $elem[$field] === $search) {
                    return $key;

                } elseif (is_scalar($elem) && $elem === $search) {
                    return $key;
                }

            } else {
                if (is_object($elem)) {
                    $elem = (array)$elem;
                    if (in_array($search, $elem)) {
                        return $key;
                    }

                } elseif (is_array($elem) && in_array($search, $elem)) {
                    return $key;

                } elseif (is_scalar($elem) && $elem === $search) {
                    return $key;
                }
            }
        }

        return false;
    }

    /**
     * Returns an array containing all the elements of arr1 after applying
     * the callback function to each one. 使用特定function对数组中所有元素做处理
     *
     * @param  string $callback Callback function to run for each element in each array
     * @param  array $array An array to run through the callback function
     * @param  boolean $onNoScalar Whether or not to call the callback function on nonscalar values
     *                             (Objects, resources, etc)
     * @return array
     */
    public static function mapDeep(array $array, $callback, $onNoScalar = false) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $args = array($value, $callback, $onNoScalar);
                $array[$key] = call_user_func_array(array(__CLASS__, __FUNCTION__), $args);

            } elseif (is_scalar($value) || $onNoScalar) {
                $array[$key] = call_user_func($callback, $value);
            }
        }

        return $array;
    }

    /**
     * Searches for a given value in an array of arrays, objects and scalar
     * values. You can optionally specify a field of the nested arrays and
     * objects to search in.
     *
     * @param  array $array The array to search
     * @param  scalar $search The value to search for
     * @param  string $field The field to search in, if not specified
     *                         all fields will be searched
     * @return boolean|scalar  False on failure or the array key on success
     */
    public static function searchDeep(array $array, $search, $field = false) {
        // *grumbles* stupid PHP type system
        $search = (string)$search;
        foreach ($array as $key => $elem) {
            // *grumbles* stupid PHP type system
            $key = (string)$key;
            if ($field) {
                if (is_object($elem) && $elem->{$field} === $search) {
                    return $key;
                } elseif (is_array($elem) && $elem[$field] === $search) {
                    return $key;
                } elseif (is_scalar($elem) && $elem === $search) {
                    return $key;
                }
            } else {
                if (is_object($elem)) {
                    $elem = (array)$elem;
                    if (in_array($search, $elem)) {
                        return $key;
                    }
                } elseif (is_array($elem) && in_array($search, $elem)) {
                    return $key;
                } elseif (is_scalar($elem) && $elem === $search) {
                    return $key;
                }
            }
        }
        return false;
    }

    /**
     * Clean array by custom rule
     *
     * @param array $haystack
     * @return array
     */
    public static function clean($haystack) {
        return array_filter($haystack);
    }

    /**
     * Clean array before serialize to JSON
     *
     * @param array $array
     * @return array
     */
    public static function cleanBeforeJson(array $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::cleanBeforeJson($array[$key]);
            }

            if ($array[$key] === '' || is_null($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Check is array is type assoc
     *
     * @param $array
     * @return bool
     */
    public static function isAssoc($array) {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Add cell to the start of assoc array
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public static function unshiftAssoc(array &$array, $key, $value) {
        $array = array_reverse($array, true);
        $array[$key] = $value;
        $array = array_reverse($array, true);

        return $array;
    }

    /**
     * Recursive array mapping
     *
     * @param \Closure $function
     * @param array $array
     * @return array
     */
    public static function map($function, $array) {
        $result = array();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::map($function, $value);
            } else {
                $result[$key] = call_user_func($function, $value);
            }
        }

        return $result;
    }

    /**
     * Sort an array by keys based on another array
     *
     * @param array $array
     * @param array $orderArray
     * @return array
     */
    public static function sortByArray(array $array, array $orderArray) {
        return array_merge(array_flip($orderArray), $array);
    }

    /**
     * Convert assoc array to comment style
     *
     * @param array $data
     * @return string
     */
    public static function toComment(array $data) {
        $result = array();
        foreach ($data as $key => $value) {
            $result[] = $key . ': ' . $value . ';';
        }

        return implode(PHP_EOL, $result);
    }

    /**
     * @param string $glue
     * @param array $array
     * @return string
     */
    public static function implode($glue, array $array) {
        $result = '';

        foreach ($array as $item) {
            if (is_array($item)) {
                $result .= self::implode($glue, $item) . $glue;
            } else {
                $result .= $item . $glue;
            }
        }

        if ($glue) {
            $result = substr($result, 0, 0 - Str::len($glue));
        }

        return $result;
    }

    /**
     * 将一个二维数组按照指定字段的值分组
     *
     * @param array $array
     * @param string $key
     *
     * @return array
     */
    public static function group($arr, $key) {
        $grouped = [];
        foreach ($arr as $value) {
            if (is_object($value)) {
                if (isset($value->{$key})) {
                    $grouped[$value->{$key}][] = $value;
                }
            } elseif (is_array($value)) {
                if (self::exists($value, $key)) {
                    $grouped[$value[$key]][] = $value;
                }
            }
        }
        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('self::group', $parms);
            }
        }
        return $grouped;
    }

    /**
     * 一般用于将自动生成的数字索引下标改为某一项有序的值
     *
     * @param $array    待转换的数组
     * @param string $key 此项为转换的有序值key名称。若为无序，同值会被替换。
     * @return array
     */
    public static function reset($array, $key = 'id', $value = "") {
        $arrayFormat = array();

        if (!is_array($array) || !count($array)) return $arrayFormat;
        foreach ($array as $v) {
            if (isset($v[$key])) {
                $arrayFormat[$v[$key]] = isset($v[$value]) && $value ? $v[$value] : $v;
            }
        }

        return $arrayFormat;
    }
}
