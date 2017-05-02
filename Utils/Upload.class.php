<?php

/**
 * 文件上传类:  适合JS单文件，多文件的上传控件，如uploadify,kindeditor
 *
 * @author          hudy
 * @version         0.1
 * @copyright      (C) 2015- *
 *
 * $upload = new Upload($path, $fileFormat, $maxSize);
 * //$upload->setWatermark($wpath);
 * $result = $upload->execute('upload');
 */
namespace Utils;

class Upload
{
    public $saveName; // 保存名
    public $savePath; // 附件存放物理目录
    public $relativePath = ''; // 相对路径
    public $ext; // 文件扩展名
    public $overwrite; // 覆盖文件
    public $fileFormat = null; //文件格式限定
    public $filter = array("php", "asp", "aspx", "jsp", "exe"); //过滤后缀名

    public $thumb = 0; // 是否生成缩略图
    public $thumbWidth = 200; // 缩略图宽
    public $thumbHeight = 200; // 缩略图高
    public $thumbPrefix = "_thumb_"; // 缩略图前缀
    public $maxSize; //最大允许文件大小，单位为KB

    public $errno; // 错误代号
    public $returnArray = array(); // 所有文件的返回信息
    public $returninfo = array(); // 每个文件返回信息

    private $watermark_file; //水印图片地址
    private $watermark_pos; //水印位置
    private $watermark_trans; //水印透明度

    /**
     * @apiDescription                构造函数
     * @apiParam {string} $savePath    文件保存路径
     * @apiParam {mixed} $fileFormat   文件格式限制数组
     * @apiParam {string} $maxSize     文件最大尺寸KB
     * @apiParam {string} $overwriet   是否覆盖 1 允许覆盖 0 禁止覆盖
     */
    function __construct($savePath, $fileFormat = 'image', $maxSize = 0, $overwrite = 0) {
        $this->setSavepath($savePath);
        $this->setFileformat($fileFormat);
        $this->maxSize = $maxSize * 1024;
        $this->overwrite = $overwrite;
        $this->errno = 0;
    }

    // 设置文件保存路径
    protected function setSavepath($savePath) {
        $savePath = str_replace(array('{y}', '{m}', '{d}'), array(date('Y'), date('m'), date('d')), strtolower($savePath));
        $this->savePath = substr(str_replace("\\", "/", $savePath), -1) == "/" ? $savePath : $savePath . "/";
    }

    // 设置文件格式限定
    protected function setFileformat($fileFormat) {
        $result = array();

        $ext_arr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
        );
        $fileFormat = isset($ext_arr[$fileFormat]) ? $ext_arr[$fileFormat] : '';
        foreach ($fileFormat as $v) {
            if (!in_array($v, $this->filter)) {
                $result[] = $v;
            }
        }

        $this->fileFormat = $result;
    }

    /**
     * @apiDescription              设置保存的相对路径
     * @apiParam {string} $saveName  保存的文件
     */
    protected function setRelativePathName($saveName) {
        return str_replace(array($this->relativePath, "\\"), array('', "/"), $this->savePath . $saveName);
    }

    protected function setSavename() {
        $uniqid = uniqid(rand());
        $name = $uniqid . '.' . $this->ext;
        $this->saveName = $name;
    }

    protected function getExt($fileName) {
        $ext = explode(".", $fileName);
        $ext = $ext[count($ext) - 1];
        $this->ext = strtolower($ext);
    }

    // 文件格式检查,MIME检测
    protected function validateFormat() {
        if (!is_array($this->fileFormat) || in_array($this->ext, $this->fileFormat) || in_array(strtolower($this->returninfo['type']), $this->fileFormat))
            return true;
        else
            return false;
    }

    /**
     * @apiDescription                 设置缩略图
     * @apiParam {string} $thumbWidth  是缩略图的宽
     * @apiParam {string} $thumbHeight 是缩略图的高
     * @apiParam {string} [$thumb = 1] 产生缩略图
     */
    protected function setThumb($thumb, $thumbWidth = 0, $thumbHeight = 0) {
        $this->thumb = $thumb;
        if ($thumbWidth) $this->thumbWidth = $thumbWidth;
        if ($thumbHeight) $this->thumbHeight = $thumbHeight;
    }

    /**
     * @apiDescription               单个文件上传
     * @apiParam {string} $fileArray  文件信息数组
     */
    protected function copyfile($fileArray) {
        $this->returninfo = array();
        $this->returninfo['name'] = $fileArray['name'];
        $this->returninfo['saveName'] = $this->setSavename();
        $this->returninfo['md5'] = @md5_file($fileArray['tmp_name']);
        $this->returninfo['size'] = $fileArray['size'];
        $this->returninfo['type'] = $fileArray['type'];
        if (!$this->validateFormat()) {
            $this->errno = 11;
            return false;
        }
        if (!$this->fileFilter($fileArray["tmp_name"], $this->ext)) { //文件后缀验证
            $this->errno = 23;
            return false;
        }
        $this->returninfo['path'] = $this->setRelativePathName($this->saveName);
        $this->makeDirectory($this->savePath);
        if (!@is_writable($this->savePath)) {
            @mkdir($this->savePath, 0777, true);
        }
        if ($this->overwrite == 0 && @file_exists($this->savePath . $this->saveName) && is_file($this->savePath . $this->saveName)) {
            $this->errno = 13;
            return false;
        }
        if ($this->maxSize != 0) {
            if ($fileArray["size"] > $this->maxSize) {
                $this->errno = 14;
                return false;
            }
        }
        // 文件上传
        if (!@move_uploaded_file($fileArray["tmp_name"], $this->savePath . $this->saveName)) {
            $this->errno = $fileArray["error"];
            return false;
        } elseif ($this->thumb) {
            $thumbName = $this->thumbWidth . "X" . $this->thumbHeight . $this->thumbPrefix . $this->saveName;
            $this->returninfo['thumbPath'] = $this->setRelativePathName($thumbName);
            $this->createThumb($this->savePath . $this->saveName, $this->savePath . $thumbName);
        }
        $this->createWatermark($this->savePath . $this->saveName);
        // 删除临时文件
        /*if (!@$this->del($fileArray["tmp_name"])) {
            return false;
        }*/
        return true;
    }

    protected function fileFilter($path, $ext) {
        if ($this->getFileType($path, $this->ext) == $ext) {
            return true;
        } else {
            return false;
        }
    }

    protected function getFileType($file_path, $ext = '') {
        $fp = fopen($file_path, 'r');
        $bin = fread($fp, 2);
        fclose($fp);
        $strInfo = @unpack("C2chars", $bin);
        $typeCode = intval($strInfo['chars1'] . $strInfo['chars2']);
        $fileType = 'unknown';
        $typeCode == '3780' && $fileType = "pdf";
        $typeCode == '6787' && $fileType = "swf";
        $typeCode == '7784' && $fileType = "midi";
        $typeCode == '7790' && $fileType = "exe";
        $ext == 'txt' && $fileType = "txt";
        in_array($typeCode, array('8297', '8075')) && $fileType = $ext;
        if (in_array($typeCode, array('255216', '7173', '6677', '13780'))) {
            in_array($ext, array('jpg', 'gif', 'bmp', 'png', 'jpeg')) and $fileType = $ext or $fileType = 'jpg';
        }
        if ($typeCode == '208207') {
            in_array($ext, array('wps', 'ppt', 'dot', 'xls', 'doc', 'docx')) and $fileType = $ext or $fileType = 'doc';
        }
        return $fileType;
    }

    /**
     * @apiDescription 创建目录
     * @apiParam {string} $directoryName  目录名
     */
    protected function makeDirectory($directoryName) {
        $directoryName = str_replace("\\", "/", $directoryName);
        $dirNames = explode('/', $directoryName);
        $total = count($dirNames);
        $temp = '';
        for ($i = 0; $i < $total; $i++) {
            $temp .= $dirNames[$i] . '/';
        }
        return true;
    }

    // 删除文件
    protected function del($fileName) {
        if (!@unlink($fileName)) {
            $this->errno = 15;
            return false;
        }
        return true;
    }

    /**
     * @apiDescription   创建缩略图,以相同的扩展名生成缩略图
     * @apiParam {string} $src_file  来源图像路径
     * @apiParam {string} $thumb_file  缩略图路径
     */
    protected function createThumb($src_file, $thumb_file) {
        $t_width = $this->thumbWidth;
        $t_height = $this->thumbHeight;

        if (!file_exists($src_file)) return false;

        $src_info = getimagesize($src_file);

        //如果来源图像小于或等于缩略图则拷贝源图像作为缩略图,免去操作
        if ($src_info[0] <= $t_width && $src_info[1] <= $t_height) {
            if (!copy($src_file, $thumb_file)) {
                return false;
            }
            return true;
        }

        //按比例计算缩略图大小
        if (($src_info[0] - $t_width) > ($src_info[1] - $t_height)) {
            $t_height = ($t_width / $src_info[0]) * $src_info[1];
        } else {
            $t_width = ($t_height / $src_info[1]) * $src_info[0];
        }

        //取得文件扩展名
        $type = strtolower(substr(image_type_to_extension($src_info[2]), 1));
        $CreateFunction = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);
        $SaveFunction = "image" . ($type == 'jpg' ? 'jpeg' : $type);
        if ($CreateFunction == "imagecreatefromgif" && !function_exists("imagecreatefromgif")) {
            $this->errno = 16;
            return false;
        } elseif ($CreateFunction == "imagecreatefromjpeg" && !function_exists("imagecreatefromjpeg")) {
            $this->errno = 17;
            return false;
        } elseif (!function_exists($CreateFunction)) {
            $this->errno = 18;
            return false;
        }
        $src_img = $CreateFunction($src_file);
        //创建一个真彩色的缩略图像
        $thumb_img = @imagecreatetruecolor($t_width, $t_height);

        //ImageCopyResampled函数拷贝的图像平滑度较好，优先考虑
        if (function_exists('imagecopyresampled')) {
            @imagecopyresampled($thumb_img, $src_img, 0, 0, 0, 0, $t_width, $t_height, $src_info[0], $src_info[1]);
        } else {
            @imagecopyresized($thumb_img, $src_img, 0, 0, 0, 0, $t_width, $t_height, $src_info[0], $src_info[1]);
        }

        //生成缩略图
        if ($SaveFunction == 'imagejpeg') {
            $SaveFunction($thumb_img, $thumb_file, 80);
        } else {
            $SaveFunction($thumb_img, $thumb_file);
        }

        //销毁临时图像
        @imagedestroy($src_img);
        @imagedestroy($thumb_img);
        return true;
    }

    /**
     * @apiDescription          为图片添加水印
     * @apiParam {string} $file  要添加水印的文件
     */
    protected function createWatermark($file) {

        //文件不存在则返回
        if (!file_exists($this->watermark_file) || !file_exists($file)) return;
        if (!function_exists('getimagesize')) return;

        //检查GD支持的文件类型
        $gd_allow_types = array();
        if (function_exists('imagecreatefromgif')) $gd_allow_types['image/gif'] = 'imagecreatefromgif';
        if (function_exists('imagecreatefrompng')) $gd_allow_types['image/png'] = 'imagecreatefrompng';
        if (function_exists('imagecreatefromjpeg')) $gd_allow_types['image/jpeg'] = 'imagecreatefromjpeg';

        //获取文件信息
        $fileinfo = getimagesize($file);
        $wminfo = getimagesize($this->watermark_file);

        if ($fileinfo[0] < $wminfo[0] || $fileinfo[1] < $wminfo[1]) return;

        if (array_key_exists($fileinfo['mime'], $gd_allow_types)) {
            if (array_key_exists($wminfo['mime'], $gd_allow_types)) {

                //从文件创建图像
                $temp = $gd_allow_types[$fileinfo['mime']]($file);
                $temp_wm = $gd_allow_types[$wminfo['mime']]($this->watermark_file);

                //水印位置
                switch ($this->watermark_pos) {
                    case 1 : //顶部居左
                        $dst_x = 0;
                        $dst_y = 0;
                        break;
                    case 2 : //顶部居中
                        $dst_x = ($fileinfo[0] - $wminfo[0]) / 2;
                        $dst_y = 0;
                        break;
                    case 3 : //顶部居右
                        $dst_x = $fileinfo[0];
                        $dst_y = 0;
                        break;
                    case 4 : //底部居左
                        $dst_x = 0;
                        $dst_y = $fileinfo[1];
                        break;
                    case 5 : //底部居中
                        $dst_x = ($fileinfo[0] - $wminfo[0]) / 2;
                        $dst_y = $fileinfo[1];
                        break;
                    case 6 : //底部居右
                        $dst_x = $fileinfo[0] - $wminfo[0];
                        $dst_y = $fileinfo[1] - $wminfo[1];
                        break;
                    default : //随机
                        $dst_x = mt_rand(0, $fileinfo[0] - $wminfo[0]);
                        $dst_y = mt_rand(0, $fileinfo[1] - $wminfo[1]);
                }

                if (function_exists('imagealphablending')) imagealphablending($temp_wm, True); //设定图像的混色模式
                if (function_exists('imagesavealpha')) imagesavealpha($temp_wm, True); //保存完整的 alpha 通道信息

                //为图像添加水印
                if (function_exists('imagecopymerge')) {
                    imagecopymerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wminfo[0], $wminfo[1], $this->watermark_trans);
                } else {
                    imagecopymerge($temp, $temp_wm, $dst_x, $dst_y, 0, 0, $wminfo[0], $wminfo[1]);
                }

                //保存图片
                switch ($fileinfo['mime']) {
                    case 'image/jpeg' :
                        @imagejpeg($temp, $file);
                        break;
                    case 'image/png' :
                        @imagepng($temp, $file);
                        break;
                    case 'image/gif' :
                        @imagegif($temp, $file);
                        break;
                }
                //销毁零时图像
                @imagedestroy($temp);
                @imagedestroy($temp_wm);
            }
        }
    }

    /**
     * @apiDescription 图片水印设置，如果不生成添加水印则不用设置
     * @apiParam {string} $file  水印图片
     * @apiParam {string} $pos  水印位置
     * @apiParam {string} $trans  水印透明度
     */
    public function setWatermark($file, $pos = 6, $trans = 80) {
        $this->watermark_file = $file;
        $this->watermark_pos = $pos;
        $this->watermark_trans = $trans;
    }

    /**
     * @apiDescription   执行文件上传，处理完返回一个包含上传成功或失败的文件信息数组，
     * @apiParam {string} $field  网页Form(表单)中input的名称
     */
    public function execute($field = 'Filedata') {
        if (isset ($_FILES[$field])) {
            $fileArr = $_FILES[$field];
            if (is_array($fileArr['name'])) { //上传同文件域名称多个文件

                for ($i = 0; $i < count($fileArr['name']); $i++) {
                    if (!$fileArr['tmp_name'][$i]) {
                        continue;
                    }
                    $upload['tmp_name'] = $fileArr['tmp_name'][$i];
                    $upload['name'] = $fileArr['name'][$i];
                    $upload['type'] = $fileArr['type'][$i];
                    $upload['size'] = $fileArr['size'][$i];
                    $upload['error'] = $fileArr['error'][$i];
                    $this->getExt($upload['name']);
                    if ($this->copyfile($upload)) {
                        $this->returnArray[] = $this->returninfo;
                    } else {
                        $this->returninfo['error'] = $this->errmsg();
                        $this->returnArray[] = $this->returninfo;
                    }
                }
                return ['msg' => $this->errmsg(), 'code' => $this->errno, 'data' => $this->returnArray];
            } else { //上传单个文件
                $this->getExt($fileArr['name']);
                if ($this->copyfile($fileArr)) {
                    $this->returnArray[] = $this->returninfo;
                } else {
                    $this->returninfo['error'] = $this->errmsg();
                    $this->returnArray[] = $this->returninfo;
                }
                return ['msg' => $this->errmsg(), 'code' => $this->errno, 'data' => $this->returnArray];
            }
        } else {
            $this->errno = 10;
            return ['msg' => $this->errmsg(), 'code' => $this->errno, 'data' => []];
        }
    }

    // 得到错误信息
    protected function errmsg() {
        $uploadClassError = array(
            0 => '没有错误，文件上传成功。',
            1 => '上传的文件在php.ini中超过upload_max_filesize指令。',
            2 => '上传的文件超过max_file_size那是在HTML表格中指定的。',
            3 => '上传的文件只是部分上传。',
            4 => '没有上传文件。',
            6 => '遗失一个临时文件夹。',
            7 => '未能将文件写入磁盘',
            10 => '表单上传文件名称错误！',
            11 => '上传的文件是不允许的！',
            12 => '目录不可写！',
            13 => '文件已经存在！',
            14 => '文件太大！',
            15 => '删除文件失败！',
            16 => '你的PHP版本似乎并不支持GIF缩略图。',
            17 => '你的PHP版本似乎并不支持JPEG缩略图。',
            18 => '你的PHP版本似乎没有支持图片缩略图。',
            19 => '试图复制源图像时出错。你的PHP版本（' . phpversion() . '）可能没有这种图像类型支持。',
            20 => '试图创建新图像时出现错误。',
            21 => '复制源图像到图像的缩略图时发生错误。',
            22 => '保存到文件系统的缩略图时出现错误。你确信PHP配置了读这个文件夹的写访问？',
            23 => '警告！您已经更改了文件后缀或您的上传文件包含攻击代码'
        );
        return $uploadClassError[$this->errno];
    }
}
