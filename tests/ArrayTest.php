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

use Utils\Arr;
use Utils\Vars;

/**
 * Class ArrayTest
 * @package JBZoo\PHPUnit
 */
class ArrayTest extends PHPUnit
{

    public function array_unique() {
        $array = array(10, 100, 1231, 10, 600, 20, 40, 1231, 20, 6, 1);
        isSame(array(10, 100, 1231, 600, 20, 40, 6, 1), Arr::unique($array));

        $array = array('hello', 'world', 'this', 'is', 'a', 'test', 'hello', 'is', 'a', 'word');
        isSame(array('hello', 'world', 'this', 'is', 'a', 'test', 'word'), Arr::unique($array, false));

        $array = array(
            'asd_1' => 'asd',
            'asd_2' => 'asd',
        );
        isSame(array('asd_1' => 'asd'), Arr::unique($array, true));
    }

    public function array_only() {
        $array = array('name' => 'Joe', 'age' => 27, 'votes' => 1);
        $array = Arr::only($array, array('name', 'votes'));
    }

    function array_pluck() {
        $array = array(array('name' => 'Taylor'), array('name' => 'Dayle'));
        $array = Arr::pluck($array, 'name');
        // array('Taylor', 'Dayle');
    }

    function array_sort() {
        $array = array(
            array('name' => 'Jill'),
            array('name' => 'Barry'),
        );

        $array = array_values(Arr::sort($array, function ($value) {
            return $value['name'];
        }));
    }

    function array_pull() {
        $array = array('name' => 'Taylor', 'age' => 27);
        $name = Arr::pull($array, 'name');
    }

    function array_set() {
        $array = array('names' => array('programmer' => 'Joe'));
        Arr::set($array, 'names.editor', 'Taylor');
    }

    public function array_dot() {
        $array = array('foo' => array('bar' => 'baz'));
        $array = Arr::dot($array);
        Arr::dd($array);
        // array('foo.bar' => 'baz');
    }

    public function array_add() {
        $array = array('foo' => 'bar');
        $array = Arr::add($array, 'key', 'value');
        Arr::dd($array);
    }

    public function array_fetch() {
        $array = array(
            array('developer' => array('name' => 'Taylor')),
            array('developer' => array('name' => 'Dayle')),
        );
        $array = Arr::fetch($array, 'developer.name');
        Arr::dd($array);
    }

    public function array_forget() {
        $array = array('names' => array('joe' => array('programmer')));
        Arr::forget($array, 'names.joe');
    }

    public function array_get() {
        $array = array('names' => array('joe' => array('programmer')));
        $value = Arr::get($array, 'names.joe');
    }

    public function array_first() {
        $array = array(100, 200, 300);
        $value = Arr::first($array, function ($key, $value) {
            return $value >= 150;
        });
    }

    public function array_where() {
        $array = array(100, 200, 300);
        $value = Arr::where($array, function ($key, $value) {
            return $value >= 150;
        });
    }

    public function array_last() {
        $test = array('a' => array('a', 'b', 'c'));
        is('c', Arr::last(Vars::get($test['a'])));
        $test = array('a' => array('a' => 'b', 'c' => 'd'));
        is('c', Arr::last(Vars::get($test['a'])));
    }

    public function testSearch() {
        $users = array(
            1 => (object)array('username' => 'brandon', 'age' => 20),
            2 => (object)array('username' => 'matt', 'age' => 27),
            3 => (object)array('username' => 'jane', 'age' => 53),
            4 => (object)array('username' => 'john', 'age' => 41),
            5 => (object)array('username' => 'steve', 'age' => 11),
            6 => (object)array('username' => 'fred', 'age' => 42),
            7 => (object)array('username' => 'rasmus', 'age' => 21),
            8 => (object)array('username' => 'don', 'age' => 15),
            9 => array('username' => 'darcy', 'age' => 33),
        );

        $test = array(
            1 => 'brandon',
            2 => 'devon',
            3 => array('troy'),
            4 => 'annie',
        );

        isFalse(Arr::search($test, 'bob'));
        is(3, Arr::search($test, 'troy'));
        is(4, Arr::search($test, 'annie'));
        is(2, Arr::search($test, 'devon', 'devon'));
        is(7, Arr::search($users, 'rasmus', 'username'));
        is(9, Arr::search($users, 'darcy', 'username'));
        is(1, Arr::search($users, 'brandon'));
    }

    public function testMapDeep() {
        $input = array(
            '<',
            'abc',
            '>',
            'def',
            array('&', 'test', '123'),
            (object)array('hey', '<>'),
        );

        $expect = array(
            '&lt;',
            'abc',
            '&gt;',
            'def',
            array('&amp;', 'test', '123'),
            (object)array('hey', '<>'),
        );

        is($expect, Arr::mapDeep($input, 'htmlentities'));
    }

    public function testClean() {
        $input = array('a', 'b', '', null, false, 0);
        $expect = array('a', 'b');
        isSame($expect, Arr::clean($input));
    }

    public function testIsAssoc() {
        isFalse(Arr::isAssoc(array('a', 'b', 'c')));
        isFalse(Arr::isAssoc(array("0" => 'a', "1" => 'b', "2" => 'c')));

        isTrue(Arr::isAssoc(array("1" => 'a', "0" => 'b', "2" => 'c')));
        isTrue(Arr::isAssoc(array("a" => 'a', "b" => 'b', "c" => 'c')));
    }

    public function testUnshiftAssoc() {
        $array = array('a' => 1, 'b' => 2, 'c' => 3);
        Arr::unshiftAssoc($array, 'new', 0);
        isSame($array, array('new' => 0, 'a' => 1, 'b' => 2, 'c' => 3));

        $array = array('a' => 1, 'b' => 2, 'c' => 3);
        $newArray = Arr::unshiftAssoc($array, 'new', 42);
        isSame($newArray, array('new' => 42, 'a' => 1, 'b' => 2, 'c' => 3));
    }

    public function testGroup() {
        $infos = array(
            array(
                'gid' => 36,
                'name' => '高二佳木斯',
                'start_time' => '2015-08-28 00:00:00',
                'pic' => '2015/08/438488a00b3219929282e3652061c2e3.png'
            ),
            array(
                'gid' => 36,
                'name' => '高二佳木斯',
                'start_time' => '2015-08-20 00:00:00',
                'pic' => '2015/08/438488a00b3219929282e3652061c2e3.png'
            ),
            array(
                'gid' => 35,
                'name' => '高二佳木斯',
                'start_time' => '2015-08-28 00:00:00',
                'pic' => '2015/08/438488a00b3219929282e3652061c2e3.png'
            ),
            array(
                'gid' => 35,
                'name' => '高二佳木斯',
                'start_time' => '2015-08-27 00:00:00',
                'pic' => '2015/08/438488a00b3219929282e3652061c2e3.png'
            ),
            array(
                'gid' => 18,
                'name' => '天书',
                'start_time' => '2015-08-24 00:00:00',
                'pic' => 'dev/2015/08/438488a00b3219929282e3652061c2e3.png'
            ),
            array(
                'gid' => 17,
                'name' => '晒黑西游',
                'start_time' => '2015-08-24 00:00:00',
                'pic' => ''
            )
        );
        Arr::group($infos, 'name');
        Arr::group($infos, 'name', 'gid');

        $array = array(
            (object)array('name' => 'Bob', 'age' => 37),
            (object)array('name' => 'Bob', 'age' => 66),
            (object)array('name' => 'Fred', 'age' => 20),
            (object)array('age' => 41),
        );
        is(array(
            'Bob' => array(
                (object)array('name' => 'Bob', 'age' => 37),
                (object)array('name' => 'Bob', 'age' => 66),
            ),
            'Fred' => array(
                (object)array('name' => 'Fred', 'age' => 20),
            ),
        ), Arr::group($array, 'name'));
    }

    public function testMapRecursive() {
        $array = array(1, 2, 3, 4, 5);
        $result = Arr::map(function ($number) {
            return ($number * $number);
        }, $array);

        is(array(1, 4, 9, 16, 25), $result);

        $array = array(1, 2, 3, 4, 5, array(6, 7, array(8, array(array(array(9))))));
        $result = Arr::map(function ($number) {
            return ($number * $number);
        }, $array);

        is(array(1, 4, 9, 16, 25, array(36, 49, array(64, array(array(array(81)))))), $result);
    }

    public function testSortByArray() {
        $array = array(
            'address' => '1',
            'name' => '2',
            'dob' => '3',
            'no_sort_1' => '4',
            'no_sort_2' => '5',
        );

        is(array(
            'dob' => '3',
            'name' => '2',
            'address' => '1',
            'no_sort_1' => '4',
            'no_sort_2' => '5',
        ), Arr::sortByArray($array, array('dob', 'name', 'address')));
    }

    public function testAddEachKey() {
        $array = array(1, 2, 3, 4, 5);
        isSame(array(
            "prefix_0" => 1,
            "prefix_1" => 2,
            "prefix_2" => 3,
            "prefix_3" => 4,
            "prefix_4" => 5,
        ), Arr::addEachKey($array, 'prefix_'));

        $array = array('a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5);
        isSame(array(
            "prefix_a" => 1,
            "prefix_b" => 2,
            "prefix_c" => 3,
            "prefix_d" => 4,
            "prefix_e" => 5,
        ), Arr::addEachKey($array, 'prefix_'));
    }

    public function testToComment() {
        $array = array(
            'Name' => 'Denis  ',
            'Date' => 2015,
        );

        is('Name: Denis  ;' . PHP_EOL . 'Date: 2015;', Arr::toComment($array));
    }

    public function testCleanBeforeJson() {
        $array = array(
            'str_empty' => '',
            'str_0' => '0',
            'str_1' => '1',
            'null' => null,
            'bool' => false,
            'num' => 1,
            'zero' => 0,
            'array' => array(
                'str_empty' => '',
                'str_0' => '0',
                'str_1' => '1',
                'null' => null,
                'bool' => false,
                'num' => 1,
                'zero' => 0,
            ),
        );

        isSame(array(
            'str_0' => '0',
            'str_1' => '1',
            'bool' => false,
            'num' => 1,
            'zero' => 0,
            'array' => array(
                'str_0' => '0',
                'str_1' => '1',
                'bool' => false,
                'num' => 1,
                'zero' => 0,
            ),
        ), Arr::cleanBeforeJson($array));
    }

    public function testIsAttr() {
        $array = array(
            'key' => 'asd',
            'null' => null,
            'false' => false,
        );

        isTrue(Arr::key('key', $array));
        isTrue(Arr::key('null', $array));
        isTrue(Arr::key('false', $array));

        isSame('asd', Arr::key('key', $array, true));
        isSame(null, Arr::key('undefined', $array, true));
        isSame(true, Arr::key('key', $array, false));
        isSame(false, Arr::key('undefined', $array, false));

        isFalse(Arr::key('undefined', $array));
        isFalse(Arr::key('', $array));
        isFalse(Arr::key(null, $array));
        isFalse(Arr::key(false, $array));
    }

    public function testIn() {
        $array = array(
            'key' => 'asd',
            'null' => null,
            'some-bool' => false,
            'some-string' => '1234567890098765432111111',
            'some-int' => 1111112345678900987654321,
        );

        isFalse(Arr::in(0, $array));
        isTrue(Arr::in(false, $array));

        isFalse(Arr::in(0, $array, false));
        isTrue(Arr::in(false, $array, false));

        isSame('some-string', Arr::in('1234567890098765432111111', $array, true));
        isTrue('some-int', Arr::in(1111112345678900987654321, $array, true));
        isTrue('some-bool', Arr::in(false, $array, true));
    }

    public function array_flatten() {
        $array = array('name' => 'Joe', 'languages' => array('PHP', 'Ruby'));
        $array = array_flatten($array);
        // array('Joe', 'PHP', 'Ruby');
    }

    public function testImplodeNested() {
        isSame('1,2,3', Arr::implode(',', array(1, 2, 3)));
        isSame('123', Arr::implode('', array(1, 2, 3)));
        isSame('1,2,3,4,5,6', Arr::implode(',', array(1, 2, 3, array(4, 5, 6))));
        isSame('123456', Arr::implode('', array(1, 2, 3, array(4, 5, 6))));

        isSame(
            '1|||||||2|||||||3|||||||4|||||||5|||||||6|||||||7|||||||8|||||||9',
            Arr::implode('|||||||', array(1, 2, 3, array(4, 5, 6, array(7, 8, 9))))
        );

        isSame('1,2,3', Arr::implode(',', array('key1' => 1, 'key2' => 2, 'key3' => 3)));
    }

    public function test(){
        $arr=[
            array(
                'name'=>'小坏龙',
                'age'=>28
            ),
            array(
                'name'=>'小坏龙2',
                'age'=>14
            )
        ];
        $aa =  Arr::column_sort($arr, 'age');
    }
}
