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

use Utils\Str;
use Utils\Slug;

/**
 * Class StringTest
 * @package JBZoo\PHPUnit
 */
class StringTest extends PHPUnit
{

    public function testStrip()
    {
        $input  = ' The quick brown fox jumps over the lazy dog ';
        $expect = 'Thequickbrownfoxjumpsoverthelazydog';
        is($expect, Str::stripSpace($input));
    }

    public function testClean()
    {
        $input = ' <b>ASDF</b> !@#$%^&*()_+"\';:>< ';

        isSame('ASDF !@#$%^&*()_+"\';:><', Str::clean($input));
        isSame('asdf !@#$%^&*()_+\\"\\\';:><', Str::clean($input, true, true));
    }

    public function testParseLines()
    {
        isSame(array('asd'), Str::parseLines('asd', false));
        isSame(array('asd' => 'asd'), Str::parseLines('asd', true));
        isSame(array('asd' => 'asd'), Str::parseLines('asd'));

        $lines = array('', false, 123, 456, ' 123   ', '      ', 'ASD', '0');

        isSame(array(
            '123' => '123',
            '456' => '456',
            'ASD' => 'ASD',
            '0'   => '0',
        ), Str::parseLines(implode("\r", $lines), true));

        isSame(array(
            '123' => '123',
            '456' => '456',
            'ASD' => 'ASD',
            '0'   => '0',
        ), Str::parseLines(implode("\n", $lines), true));

        isSame(array(
            '123',
            '456',
            '123',
            'ASD',
            '0',
        ), Str::parseLines(implode("\r\n", $lines), false));
    }

    public function testHtmlentities()
    {
        is('One &amp; Two &lt;&gt; &amp;mdash;', Str::htmlEnt('One & Two <> &mdash;'));
        is('One &amp; Two &lt;&gt; &mdash;', Str::htmlEnt('One &amp; Two <> &mdash;', true));
    }


    public function testRandom()
    {
        is(10, strlen(Str::random()));
        is(10, strlen(Str::random(10)));

        isNotSame(Str::random(), Str::random());
        isNotSame(Str::random(), Str::random());
        isNotSame(Str::random(), Str::random());
    }

    public function testZeroPad()
    {
        is('341', Str::zeroPad('0341', 1));
        is('341', Str::zeroPad(341, 3));
        is('0341', Str::zeroPad(341, 4));
        is('000341', Str::zeroPad(341, 6));
    }

    public function testLike()
    {
        isTrue(Str::like('a', 'a'));
        isTrue(Str::like('test/*', 'test/first/second'));
        isTrue(Str::like('*/test', 'first/second/test'));
        isTrue(Str::like('test', 'TEST', false));

        isFalse(Str::like('a', ' a'));
        isFalse(Str::like('first/', 'first/second/test'));
        isFalse(Str::like('test', 'TEST'));
        isFalse(Str::like('/', '/something'));
    }

    public function testSlug()
    {
        is('a-simple-title', Slug::filter(' A simple     title '));
        is('this-post-it-has-a-dash', Slug::filter('This post -- it has a dash'));
        is('123-1251251', Slug::filter('123----1251251'));
        is('one23-1251251', Slug::filter('123----1251251', '-', true));

        is('a-simple-title', Slug::filter('A simple title', '-'));
        is('this-post-it-has-a-dash', Slug::filter('This post -- it has a dash', '-'));
        is('123-1251251', Slug::filter('123----1251251', '-'));
        is('one23-1251251', Slug::filter('123----1251251', '-', true));

        is('a_simple_title', Slug::filter('A simple title', '_'));
        is('this_post_it_has_a_dash', Slug::filter('This post -- it has a dash', '_'));
        is('123_1251251', Slug::filter('123----1251251', '_'));
        is('one23_1251251', Slug::filter('123----1251251', '_', true));

        // Blank seperator tests
        is('asimpletitle', Slug::filter('A simple title', ''));
        is('thispostithasadash', Slug::filter('This post -- it has a dash', ''));
        is('1231251251', Slug::filter('123----1251251', ''));
        is('one231251251', Slug::filter('123----1251251', '', true));
    }

    public function testEsc()
    {
        isSame(
            '&lt;a href="/test"&gt;Test !@#$%^&amp;*()_+\/&lt;/a&gt;',
            Str::esc('<a href="/test">Test !@#$%^&*()_+\\/</a>')
        );
    }

    public function testEscXML()
    {
        isSame(
            '&lt;a href=&quot;/test&quot;&gt;Test!@#$%^&amp;*()_+\/&lt;/a&gt;',
            Str::escXml('<a href="/test">Test!@#$%^&*()_+\\/</a>')
        );
    }

    public function testSplitCamelCase()
    {
        isSame('_', Str::snake('_'));
        isSame('word', Str::snake('word'));
        isSame('word_and_word', Str::snake('wordAndWord'));
        isSame('word_123_number', Str::snake('word123Number'));
        isSame('word number', Str::snake('wordNumber', ' '));
    }

    public function testGenerateUUID()
    {
        isNotSame(Str::uuid(), Str::uuid());
        isNotSame(Str::uuid(), Str::uuid());
        isNotSame(Str::uuid(), Str::uuid());
    }

    public function testGetClassName()
    {
        isSame('JBZoo', Str::getClassName('JBZoo'));
        isSame('JBZoo', Str::getClassName('\JBZoo'));
        isSame('CCK', Str::getClassName('\JBZoo\CCK'));
        isSame('Element', Str::getClassName('\JBZoo\CCK\Element'));
        isSame('Repeatable', Str::getClassName('\JBZoo\CCK\Element\Repeatable'));
        isSame('StringTest', Str::getClassName($this));

        isSame('StringTest', Str::getClassName($this, false));
        isSame('StringTest', Str::getClassName($this, false));
        isSame('phpunit', Str::getClassName(__NAMESPACE__, true));
    }

    public function testInc()
    {
        isSame('title (2)', Str::inc('title', null, 0));
        isSame('title(3)', Str::inc('title(2)', null, 0));
        isSame('title-2', Str::inc('title', 'dash', 0));
        isSame('title-3', Str::inc('title-2', 'dash', 0));
        isSame('title (4)', Str::inc('title', null, 4));
        isSame('title (2)', Str::inc('title', 'foo', 0));
    }

    public function test()
    {
        $queries = Str::splitSql('SELECT * FROM #__foo;SELECT * FROM #__bar;');

        isSame(array(
            'SELECT * FROM #__foo;',
            'SELECT * FROM #__bar;'
        ), $queries);

        $queries = Str::splitSql('
            ALTER TABLE `#__redirect_links` DROP INDEX `idx_link_old`;
            -- Some comment
            ALTER TABLE `#__redirect_links` MODIFY `old_url` VARCHAR(2048) NOT NULL;
            -- Some comment
            -- Some comment --
            ALTER TABLE `#__redirect_links` MODIFY `new_url` VARCHAR(2048) NOT NULL;
            -- Some comment
            ALTER TABLE `#__redirect_links` MODIFY `referer` VARCHAR(2048) NOT NULL;
            
            ALTER TABLE `#__redirect_links` ADD INDEX `idx_old_url` (`old_url`(100));
        ');

        isSame(array(
            'ALTER TABLE `#__redirect_links` DROP INDEX `idx_link_old`;',
            'ALTER TABLE `#__redirect_links` MODIFY `old_url` VARCHAR(2048) NOT NULL;',
            'ALTER TABLE `#__redirect_links` MODIFY `new_url` VARCHAR(2048) NOT NULL;',
            'ALTER TABLE `#__redirect_links` MODIFY `referer` VARCHAR(2048) NOT NULL;',
            'ALTER TABLE `#__redirect_links` ADD INDEX `idx_old_url` (`old_url`(100));'
        ), $queries);
    }
}
