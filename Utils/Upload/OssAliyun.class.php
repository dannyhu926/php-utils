<?php

namespace Utils\Upload;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * aliyuncs/oss-sdk-php
 */
class OssAliyun
{
    protected $ossClinet;

    public const accessKeyId     = 'asfd';
    public const accessKeySecret = 'asfda';
    public const endpoint        = 'https://oss-cn-beijing.aliyuncs.com';
    public const bucket          = 'asfda';
    public const fileName        = 'huadan-detail/';

    public function __construct()
    {
        $this->ossClinet = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint);
        $this->ossClinet->setConnectTimeout(600);//设置建立连接的超时时间，单位秒
    }

    /**
     * 文件上传
     *
     * @param $file_name
     * @param $file_path
     *
     * @return array
     */
    public function importFile($file_name, $file_path): array
    {
        try {
            $this->ossClinet->uploadFile(self::bucket, self::fileName . $file_name, $file_path);

            $result = ['code' => 0, 'msg' => '上传成功', 'data' => $file_name];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * 文件流上传
     *
     * @param $file_name
     * @param $content
     *
     * @return array
     */
    public function importObject($file_name, $content): array
    {
        try {
            $this->ossClinet->putObject(self::bucket, self::fileName . $file_name, $content);

            $result = ['code' => 0, 'msg' => '上传成功', 'data' => $file_name];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * 文件删除
     *
     * @param string $file_name
     *
     * @return array
     */
    public function deleteFile($file_name = ''): array
    {
        try {
            $this->ossClinet->deleteObject(self::bucket, self::fileName . $file_name);

            $result = ['code' => 0, 'msg' => '删除成功'];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * 判断文件是否存在
     *
     * @param string $file_name
     *
     * @return array
     */
    public function doesFileExist($file_name = ''): array
    {
        try {
            $res = $this->ossClinet->doesObjectExist(self::bucket, self::fileName . $file_name);

            $result = ['code' => 0, 'msg' => '', 'data' => $res];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * 追加上传
     *
     * @param $position
     * @param $file_name
     * @param $content
     *
     * @return array
     */
    public function appendFile($position, $file_name, $content): array
    {
        try {
            $position = $this->ossClinet->appendObject(self::bucket, self::fileName . $file_name, $content, $position);

            $result = ['code' => 0, 'msg' => '上传成功', 'data' => $position];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * 下载到本地内存.
     *
     * @param $object 存储空间名称
     *
     * @return string
     */
    public function readFile($object)
    {
        try {
            $content = $this->ossClinet->getObject(self::bucket, $object);
            $result = ['code' => 0, 'msg' => '下载到本地内存成功', 'data' => $content];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }
	
    /**
     * 获取目录下文件和子目录列表成功.
     *
     * @param $options
     * @param mixed $prefix
     *
     * @return array
     */
    public function listFiles($prefix)
    {
        try {
            $options = [
                'delimiter' => '/', //文件夹的分隔符
                'prefix' => rtrim($prefix, '/').'/', //列出prefix目录下的所有文件和文件夹
                'max-keys' => 1000,
                'marker' => '',
            ];
            $list = $this->ossClinet->listObjects(self::bucket, $options);
            $files = $dirs = [];
            if ($list) {
                foreach ($list->getObjectList() as $file) {
                    if ($file->getSize() > 0) {
                        $files[] = $file->getKey();
                    }
                }
                foreach ($list->getPrefixList() as $childDir) {
                    $dirs[] = $childDir->getPrefix();
                }
            }
            $result = ['code' => 0, 'msg' => '获取目录下文件和子目录列表成功', 'data' => ['file_list' => $files, 'dir_list' => $dirs]];
        } catch (OssException $e) {
            $result = ['code' => 1, 'msg' => $e->getMessage()];
        }

        return $result;
    }	
}