<?php
namespace Utils\Upload\Driver;

if (!class_exists('FastDFS', false)) {

    class FastDFS
    {

        protected $config = array();

        /**
         *
         * @var FastDFSTrackerClient
         */
        protected $tracker = null;

        /**
         * 初始化FastDFS上传驱动器
         *
         * @throws FastDFSException
         */
        public function __construct() {

            $fdfs_option = C('UPLOAD_OPTION.FastDFS_OPTION');

            if (!$fdfs_option['tracker'] || count($fdfs_option['tracker']) == 0) {
                throw new FastDFSException("请正确配置FastDFS的跟踪/调度服务器", -1);
            }

            //暂时不做多服务器负载处理
            $this->config['tracker']['host'] = $fdfs_option['tracker'][0]['host'];
            $this->config['tracker']['port'] = $fdfs_option['tracker'][0]['port'];
            $this->config['tracker']['timeout'] = 30;

            //看具体整合方式在觉得初始化时是否连接上调度服务器
            /*try {
                $this->tracker_get_connection();
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(),$e->getCode());
            }*/

        }

        public function __destruct() {
            $this->close();
        }

        public function getTracker() {
            return $this->tracker;
        }

        private function parseUrl($url) {
            $result = array();
            $explodeUrl = explode('/', $url);
            $result['group_name'] = $explodeUrl[1];
            $result['filename'] = $explodeUrl[2] . '/' . $explodeUrl[3] . '/' . $explodeUrl[4] . '/' . $explodeUrl[5];
            return $result;
        }

        /**
         * 获得一个tracker
         *
         * @return FastDFSTrackerClient
         */
        public function tracker_get_connection() {

            try {
                $this->tracker = new FastDFSTrackerClient($this->config['tracker']['host'], $this->config['tracker']['port'], $this->config['tracker']['timeout']);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return $this->tracker;
        }

        /**
         * 通过tracker获取一个stroage
         *
         * @param string $groupName 文件组名，当为空时，组名由tracker决定
         * @return \FastDFSStorageClient
         */
        public function tracker_query_storage_store($groupName = '') {

            try {
                if (!$this->tracker) {
                    $this->tracker_get_connection();
                }
                $storage = $this->tracker->getStorage($groupName);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return $storage;
        }

        /**
         * 上传一个文件
         *
         * @param string $localFile 本地的文件路径
         * @param string $extName 文件的扩展名，文件上传后的扩展名
         */
        public function uploadFile($localFile, $extName = '') {

            try {

                $storage = $this->tracker_query_storage_store();

                $result = $storage->uploadByFilename($localFile, $extName);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return '/' . $result['group_name'] . '/' . $result['filename'];
        }

        /**
         * 上传文件内容
         *
         * @param string $content 本地的文件路径
         * @param string $extName 文件的扩展名，文件上传后的扩展名
         */
        public function uploadContent($content, $extName) {
            try {
                $storage = $this->tracker_query_storage_store();
                $result = $storage->uploadByFileContent($content, $extName);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return '/' . $result['group_name'] . '/' . $result['filename'];
        }

        /**
         * 在storage中删除一个文件
         *
         * @param string $groupName 文件所在的组名
         * @param string $remoteFile 要删除的文件路径
         * @param FastDFSStorageClient $tracker
         * @param FastDFSStorageClient $storage
         */
        public function deleteFile($remoteFile) {

            try {

                $FileData = $this->parseUrl($remoteFile);

                $storage = $this->tracker_query_storage_store($FileData['group_name']);

                $result = $storage->deleteFile($FileData['group_name'], $FileData['filename']);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return $result;
        }


        /**
         * 检查这个文件是否已经存在
         *
         * @param string $remoteFile 文件在storage中的名字
         */
        public function isFileExist($remoteFile) {

            try {

                $FileData = $this->parseUrl($remoteFile);

                $storage = $this->tracker_query_storage_store($FileData['group_name']);

                $result = $storage->getFileInfo($FileData['group_name'], $FileData['filename']);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return $result;
        }

        public function close() {

            if ($this->tracker) {
                $this->tracker->close();
                $this->tracker = null;
            }
        }

    }

    class FastDFSBase
    {

        protected $socket = null;

        const FDFS_HEADER_LENGTH = 10; //FastDFS协议头部长度
        const FDFS_GROUP_NAME_MAX_LEN = 16; //FastDFS 存储节点组名称最大长度
        const FDFS_IP_ADDRESS_SIZE = 16; //FastDFS 存储节点IP地址长度
        const FDFS_PROTO_PKG_LEN_SIZE = 8;

        //FastDFS协议命令
        const FDFS_PROTO_CMD_ACTIVE_TEST = 111; //检查调度服务器是否正常
        const FDFS_PROTO_CMD_RESP = 100;
        const FDFS_PROTO_CMD_UPLOAD_SLAVE_FILE = 21;
        const FDFS_PROTO_CMD_DELETE_FILE = 12;
        const FDFS_PROTO_CMD_GET_METADATA = 15;
        const FDFS_PROTO_CMD_SET_METADATA = 13;
        const FDFS_PROTO_CMD_QUERY_STORE_WITHOUT_GROUP_ONE = 101; //不指定组，由调度节点根据轮询策略自动选择，并返回选择的存储节点服务器信息
        const FDFS_PROTO_CMD_QUERY_STORE_WITH_GROUP_ONE = 104; //获取指定存储节点服务器的信息
        const FDFS_STORAGE_PROTO_CMD_QUERY_FILE_INFO = 22; //获取文件信息

        //Storage 存储节点
        const FDFS_FILE_EXT_NAME_MAX_LEN = 6; //上传后文件的扩展名长度
        const FDFS_FILE_PREFIX_MAX_LEN = 16;

        //Storage 存储节点 文件meta数据
        const FDFS_OVERWRITE_METADATA = 1;

        public function connect($host, $port, $timeout = 30) {

            $this->socket = @fsockopen("tcp://$host", $port, $errno, $errstr, $timeout);

            if (!$this->socket) {
                throw new FastDFSException($errstr, -1);
            }
        }

        public function getSocket() {
            return $this->socket;
        }

        public function close() {
            fclose($this->socket);
        }

        public function read($length) {

            if (!$this->socket && feof($this->socket)) {
                throw new FastDFSException('链接失效或服务器已断开链接', -1);
            }

            $data = stream_get_contents($this->socket, $length);

            return $data;
        }

        public function send($data, $length = 0) {

            if (!$this->socket && feof($this->socket)) {
                throw new FastDFSException('链接失效或服务器已断开链接', -1);
            }

            if (!$length) {
                $length = strlen($data);
            }

            if (fwrite($this->socket, $data, $length) !== $length) {
                throw new FastDFSException('链接失效或服务器已断开链接', -1);
            }

            return true;
        }

        public static function padding($str, $len) {

            $str_len = strlen($str);

            return $str_len > $len ? substr($str, 0, $len) : $str . pack('x' . ($len - $str_len));
        }

        public static function packHeader($command, $length = 0) {
            return self::packU64($length) . pack('Cx', $command);
        }

        public static function packMetaData($data) {
            $S1 = "\x01";
            $S2 = "\x02";

            $list = array();
            foreach ($data as $key => $val) {
                $list[] = $key . $S2 . $val;
            };

            return implode($S1, $list);
        }

        public static function parseMetaData($data) {

            $S1 = "\x01";
            $S2 = "\x02";

            $arr = explode($S1, $data);
            $result = array();

            foreach ($arr as $val) {
                list($k, $v) = explode($S2, $val);
                $result[$k] = $v;
            }

            return $result;
        }

        public static function parseHeader($str, $len = FDFS_HEADER_LENGTH) {

            assert(strlen($str) === $len);

            $result = unpack('C10', $str);

            $length = self::unpackU64(substr($str, 0, 8));
            $command = $result[9];
            $status = $result[10];

            return array(
                'length' => $length,
                'command' => $command,
                'status' => $status
            );
        }

        private static function unpackU64($v) {
            list ($hi, $lo) = array_values(unpack("N*N*", $v));

            if (PHP_INT_SIZE >= 8) {
                if ($hi < 0)
                    $hi += (1 << 32); // because php 5.2.2 to 5.2.5 is totally fucked up again
                if ($lo < 0)
                    $lo += (1 << 32);

                // x64, int
                if ($hi <= 2147483647)
                    return ($hi << 32) + $lo;

                // x64, bcmath
                if (function_exists("bcmul"))
                    return bcadd($lo, bcmul($hi, "4294967296"));

                // x64, no-bcmath
                $C = 100000;
                $h = ((int)($hi / $C) << 32) + (int)($lo / $C);
                $l = (($hi % $C) << 32) + ($lo % $C);
                if ($l > $C) {
                    $h += (int)($l / $C);
                    $l = $l % $C;
                }

                if ($h == 0)
                    return $l;
                return sprintf("%d%05d", $h, $l);
            }

            // x32, int
            if ($hi == 0) {
                if ($lo > 0)
                    return $lo;
                return sprintf("%u", $lo);
            }

            $hi = sprintf("%u", $hi);
            $lo = sprintf("%u", $lo);

            // x32, bcmath
            if (function_exists("bcmul"))
                return bcadd($lo, bcmul($hi, "4294967296"));

            // x32, no-bcmath
            $hi = (float)$hi;
            $lo = (float)$lo;

            $q = floor($hi / 10000000.0);
            $r = $hi - $q * 10000000.0;
            $m = $lo + $r * 4967296.0;
            $mq = floor($m / 10000000.0);
            $l = $m - $mq * 10000000.0;
            $h = $q * 4294967296.0 + $r * 429.0 + $mq;

            $h = sprintf("%.0f", $h);
            $l = sprintf("%07.0f", $l);
            if ($h == "0")
                return sprintf("%.0f", (float)$l);
            return $h . $l;
        }

        public static function packU64($v) {


            assert(is_numeric($v));

            // x64
            if (PHP_INT_SIZE >= 8) {
                assert($v >= 0);

                // x64, int
                if (is_int($v))
                    return pack("NN", $v >> 32, $v & 0xFFFFFFFF);

                // x64, bcmath
                if (function_exists("bcmul")) {
                    $h = bcdiv($v, 4294967296, 0);
                    $l = bcmod($v, 4294967296);
                    return pack("NN", $h, $l);
                }

                // x64, no-bcmath
                $p = max(0, strlen($v) - 13);
                $lo = (int)substr($v, $p);
                $hi = (int)substr($v, 0, $p);

                $m = $lo + $hi * 1316134912;
                $l = $m % 4294967296;
                $h = $hi * 2328 + (int)($m / 4294967296);

                return pack("NN", $h, $l);
            }

            // x32, int
            if (is_int($v))
                return pack("NN", 0, $v);

            // x32, bcmath
            if (function_exists("bcmul")) {
                $h = bcdiv($v, "4294967296", 0);
                $l = bcmod($v, "4294967296");
                return pack("NN", (float)$h, (float)$l); // conversion to float is intentional; int would lose 31st bit
            }

            // x32, no-bcmath
            $p = max(0, strlen($v) - 13);
            $lo = (float)substr($v, $p);
            $hi = (float)substr($v, 0, $p);

            $m = $lo + $hi * 1316134912.0;
            $q = floor($m / 4294967296.0);
            $l = $m - ($q * 4294967296.0);
            $h = $hi * 2328.0 + $q;

            return pack("NN", $h, $l);
        }

    }

    class FastDFSTrackerClient extends FastDFSBase
    {

        private $currentTrackerInfo = array();

        private $storageObjs = array();

        public function __construct($host, $port, $timeout = 30) {

            $this->currentTrackerInfo['host'] = $host;
            $this->currentTrackerInfo['port'] = $port;

            try {
                $this->connect($host, $port, $timeout);
            } catch (FastDFSException $e) {
                throw new FastDFSException("链接FastDFS调度节点失败", -1);
            }

        }

        public function __destruct() {
            foreach ($this->storageObjs as $obj) {
                $obj->close();
            }
            $this->storageObjs = array();
        }

        public function getTrackerInfo() {
            return $this->currentTrackerInfo;
        }

        /**
         * 检查调度服务器是否正常
         *
         * @return boolean
         */
        public function isActive() {

            $header = $this->packHeader(self::FDFS_PROTO_CMD_ACTIVE_TEST, 0);

            try {
                $this->send($header);
                $resHeader = $this->parseHeader($this->read(self::FDFS_HEADER_LENGTH));
            } catch (FastDFSException $e) {
                throw new FastDFSException("FastDFS调度节点链接已断开", -1);
            }

            return $resHeader['status'] == 0 ? true : false;
        }

        public function getStorage($groupName = '') {

            $reqBody = '';
            if ($groupName) {

                if ($this->storageObjs[$groupName]) return $this->storageObjs[$groupName];

                $cmd = self::FDFS_PROTO_CMD_QUERY_STORE_WITH_GROUP_ONE;
                $len = self::FDFS_GROUP_NAME_MAX_LEN;
                $reqBody = $this->padding($groupName, $len);
            } else {

                if (count($this->storageObjs) > 0) {
                    list(, $storageObj) = each($this->storageObjs);
                    return $storageObj;
                }

                $cmd = self::FDFS_PROTO_CMD_QUERY_STORE_WITHOUT_GROUP_ONE;
                $len = 0;
            }

            $reqHeader = $this->packHeader($cmd, $len);
            try {
                $this->send($reqHeader . $reqBody);
            } catch (FastDFSException $e) {
                throw new FastDFSException("FastDFS调度节点链接已断开", -1);
            }

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            if ($resInfo['status'] != 0) {
                throw new FastDFSException("获取存储节点失败", -1);
            }

            $resBody = $resInfo['length'] ? $this->read($resInfo['length']) : '';
            $groupName = trim(substr($resBody, 0, self::FDFS_GROUP_NAME_MAX_LEN));
            $host = trim(substr($resBody, self::FDFS_GROUP_NAME_MAX_LEN, self::FDFS_IP_ADDRESS_SIZE + 1));
            list(, , $port) = unpack('N2', substr($resBody, self::FDFS_GROUP_NAME_MAX_LEN + self::FDFS_IP_ADDRESS_SIZE - 1, self::FDFS_PROTO_PKG_LEN_SIZE));

            $storeIndex = ord(substr($resBody, -1));

            try {
                $this->storageObjs[$groupName] = new FastDFSStorageClient($host, $port, 30, $groupName, $storeIndex);
            } catch (FastDFSException $e) {
                throw new FastDFSException($e->getMessage(), $e->getCode());
            }

            return $this->storageObjs[$groupName];

        }

    }

    class FastDFSStorageClient extends FastDFSBase
    {

        private $currentStorageInfo = array();


        public function __construct($host, $port, $timeout = 30, $groupName, $storeIndex) {

            $this->currentStorageInfo['host'] = $host;
            $this->currentStorageInfo['port'] = $port;
            $this->currentStorageInfo['timeout'] = $timeout;
            $this->currentStorageInfo['groupName'] = $groupName;
            $this->currentStorageInfo['storeIndex'] = $storeIndex;

            try {
                $this->connect($host, $port, $timeout);
            } catch (FastDFSException $e) {
                throw new FastDFSException("链接FastDFS存储节点失败", -1);
            }
        }

        public function getStorageInfo() {
            return $this->currentStorageInfo;
        }

        /**
         * 上传一个文件
         *
         * @param string $localFile 本地的文件路径
         * @param string $extName 文件的扩展名，文件上传后的扩展名
         * @param array $metas 文件的附加信息
         */
        public function uploadByFilename($localFile, $extName = '', $metas = array()) {

            if (!file_exists($localFile)) {
                throw new FastDFSException("需上传的本地文件不存在", -1);
            }
            $pathInfo = pathinfo($localFile);

            $extName = $extName ? $extName : $pathInfo['extension'];
            $extLen = strlen($extName);

            if ($extLen > self::FDFS_FILE_EXT_NAME_MAX_LEN) {
                throw new FastDFSException("上传文件扩展名设置的太长了", -1);
            }
            $fp = fopen($localFile, 'rb');
            flock($fp, LOCK_SH);
            $fileSize = filesize($localFile);

            $reqBodyLen = 1 + self::FDFS_PROTO_PKG_LEN_SIZE + self::FDFS_FILE_EXT_NAME_MAX_LEN + $fileSize;
            $reqHeader = $this->packHeader(11, $reqBodyLen);
            $reqBody = pack('C', $this->currentStorageInfo['storeIndex']) . $this->packU64($fileSize) . $this->padding($extName, self::FDFS_FILE_EXT_NAME_MAX_LEN);

            $this->send($reqHeader . $reqBody);

            stream_copy_to_stream($fp, $this->socket, $fileSize);
            flock($fp, LOCK_UN);
            fclose($fp);

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            if ($resInfo['status'] !== 0) {
                throw new FastDFSException("上传文件失败", -1);
            }
            $resBody = $resInfo['length'] ? $this->read($resInfo['length']) : '';
            $groupName = trim(substr($resBody, 0, self::FDFS_GROUP_NAME_MAX_LEN));

            $filePath = trim(substr($resBody, self::FDFS_GROUP_NAME_MAX_LEN));

            if ($metas) {
                $this->setFileMetaData($groupName, $filePath, $metas);
            }

            return array(
                'group_name' => $groupName,
                'filename' => $filePath
            );
        }

        /**
         * 上传文件内容
         *
         * @param string $content 文件内容
         * @param string $extName 文件的扩展名，文件上传后的扩展名
         * @param array $metas 文件的附加信息
         */
        public function uploadByFileContent($content, $extName, $metas = array()) {

            $pathInfo = pathinfo($localFile);
            $extLen = strlen($extName);

            if ($extLen > self::FDFS_FILE_EXT_NAME_MAX_LEN) {
                throw new FastDFSException("上传文件扩展名设置的太长了", -1);
            }
            $fileSize = strlen($content);

            $reqBodyLen = 1 + self::FDFS_PROTO_PKG_LEN_SIZE + self::FDFS_FILE_EXT_NAME_MAX_LEN + $fileSize;
            $reqHeader = $this->packHeader(11, $reqBodyLen);
            $reqBody = pack('C', $this->currentStorageInfo['storeIndex']) . $this->packU64($fileSize) . $this->padding($extName, self::FDFS_FILE_EXT_NAME_MAX_LEN);

            $this->send($reqHeader . $reqBody);

            /*stream_copy_to_stream($fp, $this->socket, $fileSize);
            flock($fp, LOCK_UN);
            fclose($fp);*/
            $this->send($content, $fileSize);

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            if ($resInfo['status'] !== 0) {
                throw new FastDFSException("上传文件失败", -1);
            }
            $resBody = $resInfo['length'] ? $this->read($resInfo['length']) : '';
            $groupName = trim(substr($resBody, 0, self::FDFS_GROUP_NAME_MAX_LEN));

            $filePath = trim(substr($resBody, self::FDFS_GROUP_NAME_MAX_LEN));

            if ($metas) {
                $this->setFileMetaData($groupName, $filePath, $metas);
            }

            return array(
                'group_name' => $groupName,
                'filename' => $filePath
            );
        }

        public function deleteFile($groupName, $fileName) {
            $reqBodyLen = strlen($fileName) + self::FDFS_GROUP_NAME_MAX_LEN;
            $reqHeader = $this->packHeader(self::FDFS_PROTO_CMD_DELETE_FILE, $reqBodyLen);
            $reqBody = $this->padding($groupName, self::FDFS_GROUP_NAME_MAX_LEN) . $fileName;

            $this->send($reqHeader . $reqBody);

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            return $resInfo['status'] == 0 ? true : false;
        }

        public function getFileInfo($groupName, $filePath) {

            $reqBodyLength = strlen($filePath) + self::FDFS_GROUP_NAME_MAX_LEN;
            $reqHeader = $this->packHeader(self::FDFS_STORAGE_PROTO_CMD_QUERY_FILE_INFO, $reqBodyLength);
            $reqBody = $this->padding($groupName, self::FDFS_GROUP_NAME_MAX_LEN) . $filePath;

            $this->send($reqHeader . $reqBody);

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            if (!!$resInfo['status']) {
                return false;
            }

            $resBody = $resInfo['length'] ? $this->read($resInfo['length']) : false;
            list(, , $file_size) = unpack('N2', substr($resBody, 0, self::FDFS_PROTO_PKG_LEN_SIZE));
            list(, , $create_timestamp) = unpack('N2', substr($resBody, self::FDFS_PROTO_PKG_LEN_SIZE, self::FDFS_PROTO_PKG_LEN_SIZE));
            list(, , $crc32) = unpack('N2', substr($resBody, self::FDFS_PROTO_PKG_LEN_SIZE * 2, self::FDFS_PROTO_PKG_LEN_SIZE));
            $host = trim(substr($resBody, self::FDFS_PROTO_PKG_LEN_SIZE * 3, self::FDFS_IP_ADDRESS_SIZE));
            $storeIndex = ord(substr($resBody, -1));

            return array(
                'host' => $host,
                'file_size' => $file_size,
                'create_timestamp' => $create_timestamp,
                'crc32' => $crc32,
                'storeIndex' => $storeIndex
            );
        }

        public function setFileMetaData($groupName, $filePath, array $metaData, $flag = self::FDFS_OVERWRITE_METADATA) {

            $metaData = $this->packMetaData($metaData);
            $metaDataLength = strlen($metaData);
            $filePathLength = strlen($filePath);
            $flag = $flag === self::FDFS_OVERWRITE_METADATA ? 'O' : 'M';

            $reqBodyLength = (self::FDFS_PROTO_PKG_LEN_SIZE * 2) + 1 + $metaDataLength + $filePathLength + self::FDFS_GROUP_NAME_MAX_LEN;

            $reqHeader = $this->packHeader(self::FDFS_PROTO_CMD_SET_METADATA, $reqBodyLength);

            $reqBody = $this->packU64($filePathLength) . $this->packU64($metaDataLength);
            $reqBody .= $flag . $this->padding($groupName, self::FDFS_GROUP_NAME_MAX_LEN) . $filePath . $metaData;

            $this->send($reqHeader . $reqBody);

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            return $resInfo['status'] == 0 ? true : false;
        }

        /**
         * 取得文件的元信息，如果文件不存在则，返回false，反正是一个关联数组
         *
         * @param type $groupName
         * @param type $filePath
         * @return boolean
         */
        public function getFileMeta($groupName, $filePath) {
            $reqBodyLength = strlen($filePath) + self::FDFS_GROUP_NAME_MAX_LEN;
            $reqHeader = $this->packHeader(self::FDFS_PROTO_CMD_GET_METADATA, $reqBodyLength);
            $reqBody = $this->padding($groupName, self::FDFS_GROUP_NAME_MAX_LEN) . $filePath;

            $this->send($reqHeader . $reqBody);

            $resHeader = $this->read(self::FDFS_HEADER_LENGTH);
            $resInfo = $this->parseHeader($resHeader);

            if (!!$resInfo['status']) {
                return false;
            }

            $resBody = $resInfo['length'] ? $this->read($resInfo['length']) : false;

            return $this->parseMetaData($resBody);
        }

    }

    class FastDFSException extends \Exception
    {

    }

}