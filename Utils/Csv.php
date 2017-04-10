<?php
/**
 *  Csv 工具类
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author   hudy <469671292@163.com>
 */

namespace Utils;

/**
 * Class Csv
 * @package JBZoo\Utils
 */
class Csv
{
    const LENGTH_LIMIT = 10000000;

    /**
     * @param string $csvFile
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $hasHeader
     * @return array
     */
    public static function parse($csvFile, $method = 'fopen', $delimiter = ';', $enclosure = '"', $hasHeader = true) {
        $result = array();

        $headerKeys = array();
        $rowCounter = 0;

        if (($handle = $method($csvFile, "r")) !== false) {
            while (($row = fgetcsv($handle, self::LENGTH_LIMIT, $delimiter, $enclosure)) !== false) {
                if ($rowCounter === 0 && $hasHeader) {
                    $headerKeys = $row;

                } else {
                    if ($hasHeader) {
                        $assocRow = array();
                        foreach ($headerKeys as $colIndex => $colName) {
                            $assocRow[$colName] = $row[$colIndex];
                        }

                        $result[] = $assocRow;

                    } else {
                        $result[] = $row;
                    }
                }

                $rowCounter++;
            }

            fclose($handle);
        }

        return $result;
    }

    public static function fopen_utf8($csvFile) {
        $handle = fopen($csvFile, 'r');
        $bom = fread($handle, 2);
        rewind($handle);
        if ($bom === chr(0xff) . chr(0xfe) || $bom === chr(0xfe) . chr(0xff)) {
            // UTF16 Byte Order Mark present
            $encoding = 'UTF-16';
        } else {
            $file_sample = fread($handle, 1000) + 'e'; //read first 1000 bytes
            rewind($handle);

            $encoding = mb_detect_encoding($file_sample, 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
        }

        if ($encoding) {
            stream_filter_append($handle, 'convert.iconv.' . $encoding . '/UTF-8');
        }
        return ($handle);
    }

    /**
     * @param string $csvFile 得到读取淘宝网店数据包csv文件
     * @return array
     */
    public static function taobao($csvFile) {
        $result = self::parse($csvFile, 'fopen_utf8', '\t', null, true);
        return $result;
    }
}
