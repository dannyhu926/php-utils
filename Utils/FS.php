<?php
/**
 * FS 文件目录操作类
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author   hudy <469671292@163.com>
 */

namespace Utils;

/**
 * Class FS
 * @package JBZoo\Utils
 */
class FS
{
    /**
     * Returns the file permissions as a nice string, like -rw-r--r-- or false if the file is not found.
     *
     * @param   string $file The name of the file to get permissions form
     * @param   int $perms Numerical value of permissions to display as text.
     * @return  string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function perms($file, $perms = null) {
        if (null === $perms) {
            if (!file_exists($file)) {
                return false;
            }

            $perms = fileperms($file);
        }

        //@codeCoverageIgnoreStart
        if (($perms & 0xC000) == 0xC000) { // Socket
            $info = 's';

        } elseif (($perms & 0xA000) == 0xA000) { // Symbolic Link
            $info = 'l';

        } elseif (($perms & 0x8000) == 0x8000) { // Regular
            $info = '-';

        } elseif (($perms & 0x6000) == 0x6000) { // Block special
            $info = 'b';

        } elseif (($perms & 0x4000) == 0x4000) { // Directory
            $info = 'd';

        } elseif (($perms & 0x2000) == 0x2000) { // Character special
            $info = 'c';

        } elseif (($perms & 0x1000) == 0x1000) { // FIFO pipe
            $info = 'p';

        } else { // Unknown
            $info = 'u';
        }
        //@codeCoverageIgnoreEnd

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

        // All
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }

    /**
     * Removes a directory (and its contents) recursively. 删除文件夹
     * Contributed by Askar (ARACOOL) <https://github.com/ARACOOOL>
     *
     * @param  string $dir The directory to be deleted recursively
     * @param  bool $traverseSymlinks Delete contents of symlinks recursively
     * @return bool
     * @throws \RuntimeException
     */
    public static function rmDir($dir, $traverseSymlinks = false) {
        if (!file_exists($dir)) {
            return true;

        } elseif (!is_dir($dir)) {
            throw new \RuntimeException('Given path is not a directory');
        }

        if (!is_link($dir) || $traverseSymlinks) {
            foreach (scandir($dir) as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $currentPath = $dir . '/' . $file;

                if (is_dir($currentPath)) {
                    self::rmdir($currentPath, $traverseSymlinks);

                } elseif (!unlink($currentPath)) {
                    // @codeCoverageIgnoreStart
                    throw new \RuntimeException('Unable to delete ' . $currentPath);
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        // @codeCoverageIgnoreStart
        // Windows treats removing directory symlinks identically to removing directories.
        if (is_link($dir) && !defined('PHP_WINDOWS_VERSION_MAJOR')) {
            if (!unlink($dir)) {
                throw new \RuntimeException('Unable to delete ' . $dir);
            }

        } else {
            if (!rmdir($dir)) {
                throw new \RuntimeException('Unable to delete ' . $dir);
            }
        }

        return true;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Binary safe to open file 将整个文件内容读出到一个字符串中
     *
     * @param $filename
     * @return null|string
     */
    public static function openFile($filename) {
        $contents = null;

        if ($realPath = self::real($filename)) {
            $handle = fopen($realPath, "rb");
            $contents = fread($handle, filesize($realPath));
            fclose($handle);
        }

        return $contents;
    }

    /**
     * Quickest way for getting first file line
     *
     * @param string $filename
     * @return string
     */
    public static function firstLine($filename) {
        if (file_exists($filename)) {
            $cacheRes = fopen($filename, 'r');
            $firstLine = fgets($cacheRes);
            fclose($cacheRes);

            return $firstLine;
        }

        return null;
    }

    /**
     * Set the writable bit on a file to the minimum value that allows the user running PHP to write to it.
     *
     * @param  string $filename The filename to set the writable bit on
     * @param  boolean $writable Whether to make the file writable or not
     * @return boolean
     */
    public static function writable($filename, $writable = true) {
        return self::_setPerms($filename, $writable, 2);
    }

    /**
     * Set the readable bit on a file to the minimum value that allows the user running PHP to read to it.
     *
     * @param  string $filename The filename to set the readable bit on
     * @param  boolean $readable Whether to make the file readable or not
     * @return boolean
     */
    public static function readable($filename, $readable = true) {
        return self::_setPerms($filename, $readable, 4);
    }

    /**
     * Set the executable bit on a file to the minimum value that allows the user running PHP to read to it.
     *
     * @param  string $filename The filename to set the executable bit on
     * @param  boolean $executable Whether to make the file executable or not
     * @return boolean
     */
    public static function executable($filename, $executable = true) {
        return self::_setPerms($filename, $executable, 1);
    }

    /**
     * Returns size of a given directory in bytes.
     *
     * @param string $dir
     * @return integer
     */
    public static function dirSize($dir) {
        $size = 0;

        $flags = \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::SKIP_DOTS;

        $dirIter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags));

        foreach ($dirIter as $key) {
            if ($key->isFile()) {
                $size += $key->getSize();
            }
        }

        return $size;
    }

    /**
     * Returns all paths inside a directory.
     *
     * @param string $dir
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.ShortMethodName)
     */
    public static function ls($dir) {
        $contents = array();

        $flags = \FilesystemIterator::KEY_AS_PATHNAME
            | \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::SKIP_DOTS;

        $dirIter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags));

        foreach ($dirIter as $path => $fi) {
            $contents[] = $path;
        }

        natsort($contents);
        return $contents;
    }

    /**
     * Nice formatting for computer sizes (Bytes).
     *
     * @param   integer $bytes The number in bytes to format
     * @param   integer $decimals The number of decimal points to include
     * @return  string
     */
    public static function format($bytes, $decimals = 2) {
        $exp = 0;
        $value = 0;
        $symbol = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        $bytes = floatval($bytes);

        if ($bytes > 0) {
            $exp = floor(log($bytes) / log(1024));
            $value = ($bytes / pow(1024, floor($exp)));
        }

        if ($symbol[$exp] === 'B') {
            $decimals = 0;
        }

        return number_format($value, $decimals, '.', '') . ' ' . $symbol[$exp];
    }

    /**
     * @param string $filename
     * @param bool $isFlag
     * @param int $perm
     * @return bool
     */
    protected static function _setPerms($filename, $isFlag, $perm) {
        $stat = @stat($filename);

        if ($stat === false) {
            return false;
        }

        // We're on Windows
        if (Sys::isWin()) {
            //@codeCoverageIgnoreStart
            return true;
            //@codeCoverageIgnoreEnd
        }

        list($myuid, $mygid) = array(posix_geteuid(), posix_getgid());

        $isMyUid = $stat['uid'] == $myuid;
        $isMyGid = $stat['gid'] == $mygid;

        //@codeCoverageIgnoreStart
        if ($isFlag) {
            // Set only the user writable bit (file is owned by us)
            if ($isMyUid) {
                return chmod($filename, fileperms($filename) | intval('0' . $perm . '00', 8));
            }

            // Set only the group writable bit (file group is the same as us)
            if ($isMyGid) {
                return chmod($filename, fileperms($filename) | intval('0' . $perm . $perm . '0', 8));
            }

            // Set the world writable bit (file isn't owned or grouped by us)
            return chmod($filename, fileperms($filename) | intval('0' . $perm . $perm . $perm, 8));

        } else {
            // Set only the user writable bit (file is owned by us)
            if ($isMyUid) {
                $add = intval('0' . $perm . $perm . $perm, 8);
                return self::_chmod($filename, $perm, $add);
            }

            // Set only the group writable bit (file group is the same as us)
            if ($isMyGid) {
                $add = intval('00' . $perm . $perm, 8);
                return self::_chmod($filename, $perm, $add);
            }

            // Set the world writable bit (file isn't owned or grouped by us)
            $add = intval('000' . $perm, 8);
            return self::_chmod($filename, $perm, $add);
        }
        //@codeCoverageIgnoreEnd
    }

    /**
     * Chmod alias
     *
     * @param string $filename
     * @param int $perm
     * @param int $add
     * @return bool
     */
    protected static function _chmod($filename, $perm, $add) {
        return chmod($filename, (fileperms($filename) | intval('0' . $perm . $perm . $perm, 8)) ^ $add);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function ext($path) {
        if (strpos($path, '?') !== false) {
            $path = preg_replace('#\?(.*)#', '', $path);
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        return $ext;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function base($path) {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function filename($path) {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function dirname($path) {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * @param string $path
     * @return string
     */
    public static function real($path) {
        return realpath($path);
    }

    /**
     * Function to strip additional / or \ in a path name.
     *
     * @param   string $path The path to clean.
     * @param   string $dirSep Directory separator (optional).
     * @return  string
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function clean($path, $dirSep = '/') {
        if (!is_string($path) || empty($path)) {
            return '';
        }

        $path = trim((string)$path);

        if (empty($path)) {
            $path = Vars::get($_SERVER['DOCUMENT_ROOT'], '');

        } elseif (($dirSep == '\\') && ($path[0] == '\\') && ($path[1] == '\\')) {
            $path = "\\" . preg_replace('#[/\\\\]+#', $dirSep, $path);

        } else {
            $path = preg_replace('#[/\\\\]+#', $dirSep, $path);
        }

        return $path;
    }

    /**
     * Strip off the extension if it exists.
     *
     * @param string $path
     * @return string
     */
    public static function stripExt($path) {
        $reg = '/\.' . preg_quote(self::ext($path)) . '$/';
        $path = preg_replace($reg, '', $path);

        return $path;
    }

    /**
     * Check is current path directory
     * @param string $path
     * @return bool
     */
    public static function isDir($path) {
        $path = self::clean($path);
        return is_dir($path);
    }

    /**
     * Check is current path regular file
     * @param string $path
     * @return bool
     */
    public static function isFile($path) {
        $path = self::clean($path);
        return file_exists($path) && is_file($path);
    }

    /**
     * Find relative path of file (remove root part)
     *
     * @param string $filename
     * @param string|null $rootPath
     * @param string $forceDS
     * @param bool $toRealpath
     * @return mixed
     */
    public static function getRelative($filename, $rootPath = null, $forceDS = DIRECTORY_SEPARATOR, $toRealpath = true) {
        // Cleanup file path
        if ($toRealpath && !self::isReal($filename)) {
            $filename = self::real($filename);
        }
        $filename = self::clean($filename, $forceDS);


        // Cleanup root path
        $rootPath = $rootPath ? : Sys::getDocRoot();
        if ($toRealpath && !self::isReal($rootPath)) {
            $rootPath = self::real($rootPath);
        }
        $rootPath = self::clean($rootPath, $forceDS);


        // Remove root part
        $relPath = preg_replace('#^' . preg_quote($rootPath) . '#i', '', $filename);
        $relPath = ltrim($relPath, $forceDS);

        return $relPath;
    }

    /**
     * @param $path
     * @return bool
     */
    public static function isReal($path) {
        $expected = self::clean(self::real($path));
        $actual = self::clean($path);

        return $expected === $actual;
    }

    /**
     * 删除文件
     *
     * @param  string $path
     * @return  boolean
     */
    public static function rmFile($path) {
        if (self::isFile($path)) {
            if (!unlink($path)) {
                throw new \RuntimeException('Unable to delete ' . $path);
            }
        }
        return true;
    }

    /**
     * 建立文件
     *
     * @param  string $filename
     * @param  boolean $overWrite 该参数控制是否覆盖原文件
     * @return  boolean
     */
    public static function createFile($filename, $overWrite = false) {
        if ($overWrite == true) {
            self::rmFile($filename);
        }
        $aimDir = self::dirname($filename);
        self::createDir($aimDir);
        touch($filename);
        return true;
    }

    /**
     * 复制文件
     *
     * @param  string $fileUrl
     * @param  string $filename
     * @param  boolean $overWrite 该参数控制是否覆盖原文件
     * @return  boolean
     */
    private function _copyFile($fileUrl, $filename, $overWrite = false) {
        if ($overWrite == true) {
            self::rmFile($filename);
        }
        $aimDir = self::dirname($filename);
        self::createDir($aimDir);
        copy($fileUrl, $filename);
        return true;
    }

    /**
     * 移动文件
     *
     * @param  string $fileUrl
     * @param  string $filename
     * @param  boolean $overWrite 该参数控制是否覆盖原文件
     * @return  boolean
     */
    private function _moveFile($fileUrl, $filename, $overWrite = false) {
        if ($overWrite == true) {
            self::rmFile($filename);
        }
        $aimDir = self::dirname($filename);
        self::createDir($aimDir);
        rename($fileUrl, $filename);
        return true;
    }

    /**
     * 复制文件夹
     *
     * @param  string $oldDir
     * @param  string $aimDir
     * @param  boolean $overWrite 该参数控制是否覆盖原文件
     * @return  boolean
     */
    private function _copyDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            self::createDir($aimDir);
        }
        $dirHandle = opendir($oldDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            static::copy($oldDir . $file, $aimDir . $file, $overWrite);
        }
        return closedir($dirHandle);
    }

    /**
     * 移动文件夹
     *
     * @param  string $oldDir
     * @param  string $aimDir
     * @param  boolean $overWrite 该参数控制是否覆盖原文件
     * @return  boolean
     */
    private function _moveDir($oldDir, $aimDir, $overWrite = false) {
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            self::createDir($aimDir);
        }
        @$dirHandle = opendir($oldDir);
        if (!$dirHandle) {
            return false;
        }
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            static::move($oldDir . $file, $aimDir . $file, $overWrite);
        }
        closedir($dirHandle);
        return rmdir($oldDir);
    }


    /**
     * 建立文件夹
     *
     * @param  string $filename
     * @return  viod
     */
    public static function createDir($filename, $mode = 0775) {
        $aimDir = '';
        $arr = explode('/', $filename);
        foreach ($arr as $str) {
            $aimDir .= $str . '/';
            if (!self::isDir($aimDir)) {
                mkdir($aimDir, $mode);
            }
        }
    }

    /**
     * 目录列表
     *
     * @param    string $dir 路径
     * @param    int $parentid 父id
     * @param    array $dirs 传入的目录
     * @return    array    返回目录及子目录列表
     */
    public static function dirTree($dir, $parentid = 0, $dirs = array()) {
        global $id;
        if ($parentid == 0)
            $id = 0;
        $list = glob($dir . '*');
        foreach ($list as $v) {
            if (is_dir($v)) {
                $id++;
                $dirs [$id] = array('id' => $id, 'parentid' => $parentid, 'name' => self::base($v), 'dir' => self::clean($v) . '/');
                $dirs = self::dirTree($v . '/', $id, $dirs);
            }
        }
        return $dirs;
    }

    /**
     * 目录列表下的一级子目录
     *
     * @param    string $dir 路径
     * @return    array    返回目录列表
     */
    public static function dirChildNode($dir) {
        $d = dir($dir);
        $dirs = array();
        while (false !== ($entry = $d->read())) {
            if ($entry != '.' and $entry != '..' and is_dir($dir . '/' . $entry)) {
                $dirs[] = $entry;
            }
        }
        return $dirs;
    }

    /**
     * 转换目录下面的所有文件编码格式
     *
     * @param    string $in_charset 原字符集
     * @param    string $out_charset 目标字符集
     * @param    string $dir 目录地址
     * @param    string $fileexts 转换的文件格式
     * @return    string    如果原字符集和目标字符集相同则返回false，否则为true
     */
    function lsIconv($in_charset, $out_charset, $dir, $fileexts = 'php|html|htm|shtml|shtm|js|txt|xml') {
        if ($in_charset == $out_charset)
            return false;
        $list = self::ls($dir);
        foreach ($list as $v) {
            if (preg_match("/\.($fileexts)/i", $v) && is_file($v)) {
                file_put_contents($v, iconv($in_charset, $out_charset, file_get_contents($v)));
            }
        }
        return true;
    }

    /**
     * 将字符串写入文件
     *
     * @param  string $filename 文件名
     * @param  boolean $str 待写入的字符数据
     */
    public static function writeFile($filename, $str) {
        $fp = fopen($filename, "wb");
        fwrite($fp, $str);
        fclose($fp);
    }

    /**
     * 将字符串写入文件的指定行
     *
     * @param  string $filename文件的路径 ，
     * @param  string $string 要写入的字符串，
     * @param  string $line要插入 、更新、删除的行数,
     * @param  string $mode指定是插入 （w）、更新（u）、删除（d）
     */
    public static function writeLine($filename, $line, $mode = 'w', $string = '') {
        $result = false;
        if (self::isFile($filename)) {
            $fileArr = file($filename); //把文件存进数组
            $size = count($fileArr); //数组的长度
            if ($line > $size) { //如果插入的行数大于文件现有的行数，直接用系统自带的就行
                return $result;
            }
            $newFileStr = '';
            for ($i = 0; $i < $size; $i++) {
                if ($i == $line - 1) {
                    switch (strtolower($mode)) { //判断是写入，还是删除或者是更新
                        case 'w' :
                            $newFileStr .= $string . "\r\n";
                            $newFileStr .= $fileArr [$i];
                        case 'u' :
                            $newFileStr .= $string . "\r\n";
                        case 'd' :
                            continue;
                    }
                } else {
                    $newFileStr .= $fileArr[$i];
                }
            }
            self::writeFile($filename, $newFileStr);
            $result = true;
        }

        return $result;
    }

    /**
     * 将文件内容读出到一个数组中
     *
     * @param  string $filename 文件名
     * @return array
     */
    public static function readFile2array($filename) {
        $file = file($filename);
        $arr = array();
        foreach ($file as $value) {
            $arr[] = trim($value);
        }
        return $arr;
    }

    public static function __callStatic($method, $arguments) {
        if (in_array($method, ['copy', 'move'])) {
            $class_method = "_{$method}Dir";
            if (!is_dir($arguments['0'])) {
                $class_method = "{$method}File";
            }
            call_user_func_array([__CLASS__, $class_method], $arguments);
        }
    }
}