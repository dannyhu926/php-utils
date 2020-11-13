<?php
/**
 *  Csv 工具类
 *  $handle->addHead( $head );
 *  $handle->setFileName( $filename );
 *  $handle->addContents($content);
 *  $handle->saveAsFile($i);
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author    hudy <469671292@163.com>
 */

namespace Utils;

/**
 * Class Csv
 * @package JBZoo\Utils
 */
class Csv
{
    const LENGTH_LIMIT = 10000000;
    private $fileName = null; //文件名
    private $fields = null; //文件头部键名
    private $preContent = null; //标题前内容
    private $header = null; //文件头部名称
    private $contents = null; //文件内容
    private $files = []; //文件列表
    private $path = ''; //路径
    private $offset = 1; //第几个文件

    /**
     * 添加文件头部前内容.
     *
     * @param $content
     */
    public function addPreContent($content)
    {
        $this->preContent = $content;
    }

    /**
     * 添加文件头部内容.
     *
     * @param $head
     */
    public function addHead($head)
    {
        //取出数据
        $title = array_column($head, 'title');
        $key = array_column($head, 'key');
        $this->header = implode(',', $title);
        $this->fields = $key;
    }

    /**
     * 添加文件内容.
     *
     * @param $content
     */
    public function addContents($content)
    {
        foreach ($content as $value) {
            $item = '';
            //匹配数据
            foreach ($this->fields as $v) {
                if (strlen($value[$v]) > 10) {
                    $itemValue = $value[$v]."\t";
                } else {
                    $itemValue = !empty($value[$v]) ? $value[$v] : '--';
                }
                $itemValue = str_replace(',', ' ', $itemValue);
                $item .= $itemValue.',';
            }
            $item = trim($item, ',');
            $this->contents[] = $item;
        }
    }

    /**
     * 设置文件名.
     *
     * @param $fileName
     *
     * @return $this 返回当前对象
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * 生成csv文件
     * @param int $isNotFirst
     */
    public function saveAsFile($isNotFirst = 0)
    {
        $fileName = $this->path.$this->fileName.'-'.$this->offset.'.csv';
        //将头部插入内容头部
        if (empty($isNotFirst)) {
            array_unshift($this->contents, $this->header);
            if (!empty($this->preContent)) {
                array_unshift($this->contents, $this->preContent);
            }
        }
        $contents = implode(PHP_EOL, $this->contents).PHP_EOL;
        //转码，防乱码
        $contents = mb_convert_encoding($contents, 'gbk', 'utf-8');
        $dirPath = dirname($fileName);
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        //写入文件
        $result = file_put_contents($fileName, $contents, FILE_APPEND);
        if ($result) {
            $this->files[] = $fileName;
            $this->contents = [];
            $this->updateOffset();
        }
    }

    /**
     * 打包文件.
     *
     * @return bool|string 没有文件的时候返回false|压缩包文件名
     *
     * @throws \Exception
     */
    public function getZip()
    {
        $zipName = false;
        if (is_array($this->files) && count($this->files) > 0) {
            $filename = $this->fileName.'.zip';
            $zip = new \ZipArchive();
            $zipName = $this->path.$filename;
            if (is_file($zipName)) {
                unlink($zipName);
            }
            if (true !== $zip->open($zipName, \ZipArchive::CREATE)) {
                throw new \Exception('创建压缩压缩文件失败');
            }
            //加入压缩包
            foreach ($this->files as $key => $itemFilename) {
                $zip->addFile($itemFilename, basename($itemFilename));
            }
            $zip->close();
            //删除csv文件
            foreach ($this->files as $itemFilename) {
                unlink($itemFilename);
            }
        }

        return $zipName;
    }

    /**
     * 读取csv文件
     * @param string $csvFile
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $hasHeader
     * @return array
     */
    public static function parse($csvFile, $delimiter = ';', $enclosure = '"', $hasHeader = true)
    {
        $result = array();
        $headerKeys = array();
        $rowCounter = 0;
        if (($handle = fopen($csvFile, "r")) !== false) {
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

    public static function fopen_utf8($csvFile)
    {
        $handle = fopen($csvFile, 'r');
        $bom = fread($handle, 2);
        rewind($handle);
        if ($bom === chr(0xff).chr(0xfe) || $bom === chr(0xfe).chr(0xff)) {
            // UTF16 Byte Order Mark present
            $encoding = 'UTF-16';
        } else {
            $file_sample = fread($handle, 1000) + 'e'; //read first 1000 bytes
            rewind($handle);
            $encoding = mb_detect_encoding(
                $file_sample,
                'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP'
            );
        }
        if ($encoding) {
            stream_filter_append($handle, 'convert.iconv.'.$encoding.'/UTF-8');
        }

        return ($handle);
    }

    /**
     * @param string $csvFile 得到读取淘宝网店数据包csv文件
     * @return array
     */
    public static function taobao($csvFile)
    {
        $csvData = array();
        $handle = self::fopen_utf8($csvFile);
        for ($j = 1; !feof($handle); $j++) {
            $line = fgets($handle);
            $val = explode("\t", $line);
            if ($j > 1) {
                $csvData[] = $val;
            }
        }

        return $csvData;
    }

    /**
     * 更新偏移量.
     */
    private function updateOffset()
    {
        ++$this->offset;
    }
}
