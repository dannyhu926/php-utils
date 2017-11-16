<?php
namespace Utils\Upload;

class Upload
{

    static private $instance = array(); //  上传实例
    static private $_instance = null; //  当前上传实例

    /**
     * 取得上传类实例
     * @static
     * @access public
     * @return Object 返回上传驱动类
     */
    static public function getInstance() {
        $upload_option = C('UPLOAD_OPTION');
        $md5 = md5(serialize($upload_option));
        if (!isset(self::$instance[$md5])) {
            if ($upload_option['type'] == 'FastDFS') {
                $class = 'Utils\\Upload\\Driver\\' . $upload_option['type'];
                if (class_exists($class)) {
                    self::$instance[$md5] = new $class();
                } else {
                    // 类没有定义
                    throw new UploadException("指定的上传驱动器不存在", -1);
                }
            }
        }
        self::$_instance = self::$instance[$md5];
        return self::$_instance;
    }
}

class UploadException extends \Exception
{

}