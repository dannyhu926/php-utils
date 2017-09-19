<?php
/**
 * QRcodeHandler.class.php 生成二维码
 *
 * 调用示例:
 * <code>
 * $qcode = new Qcode();
 * echo $qcode->create('http://www.y1s.cn','qrcode');
 * </code>
 *
 */
include dirname(__FILE__) . '/phpqrcode.php';

class QRcodeHandler
{
    protected $error;
    protected $errorCorrectionLevel = 'L'; //容错级别
    protected $suffix = '.png'; //后缀
    private $logo_path;
    private $upload_dir;
    private $size;


    /**
     * Qcode constructor.
     *
     * @param string $upload_dir logo的绝对路径（包含图片名）
     * @param string $logo_path 生成图片的路径
     * @param int $size 生成图片的大小（缩放倍数）
     */
    public function __construct($upload_dir = '', $logo_path = '', $size = 20) {
        $this->logo_path = $logo_path;
        $this->upload_dir = $upload_dir ? $upload_dir : dirname(__FILE__);
        $this->size = $size;
    }

    public function getImg($url, $file_name, $overrite = false) {
        $img_name = $this->upload_dir . '/' . $file_name . $this->suffix;
        if (!$overrite && is_file($img_name)) {
            return $file_name . $this->suffix;
        } else {
            if ($this->create($url, $file_name)) {
                return $file_name . $this->suffix;
            }
        }

        return false;
    }

    /**
     * 生成二维码函数
     *
     * @param $url 二维码指向的地址
     * @param $file_name 生成图片的名称
     *
     * @return bool
     */
    public function create($url, $file_name) {
        if (!$this->checkUploadDir()) {
            return false;
        }
        //生成二维码图片
        $img_name = $this->upload_dir . '/' . $file_name . $this->suffix;
        QRcode::png($url, $img_name, $this->errorCorrectionLevel, $this->size, 2);
        $QR = $img_name; //已经生成的原始二维码图

        if ($this->logo_path !== FALSE) {
            $logo_name = $file_name . '_logo' . $this->suffix;
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($this->logo_path));
            $QR_width = imagesx($QR); //二维码图片宽度
            $QR_height = imagesy($QR); //二维码图片高度
            $logo_width = imagesx($logo); //logo图片宽度
            $logo_height = imagesy($logo); //logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
            //输出图片
            return imagepng($QR, $this->upload_dir . '/' . $logo_name);
        } else {
            return true;
        }
    }

    /**
     * 检查目录是否可写
     *
     * @return bool
     */
    public function checkUploadDir() {
        // 检查上传目录
        if (!is_dir($this->upload_dir)) {
            // 检查目录是否编码后的
            if (is_dir(base64_decode($this->upload_dir))) {
                $this->upload_dir = base64_decode($this->upload_dir);
            } else {
                // 尝试创建目录
                if (!mkdir($this->upload_dir)) {
                    $this->error = '上传目录' . $this->upload_dir . '不存在';
                    return false;
                }
            }
        } else {
            if (!is_writeable($this->upload_dir)) {
                $this->error = '上传目录' . $this->upload_dir . '不可写';
                return false;
            }
        }

        return true;
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }
}