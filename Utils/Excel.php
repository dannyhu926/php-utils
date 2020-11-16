<?php
/**
 *      $service->generatedFile($fileName, 0);
 *      $outputFilename = $service->getZip('开票详情'.date('Ymd').".zip");
 *      $filePath = '/data/www/html/csv/'.basename($outputFilename);
 *      $this->download($filePath);
 */

namespace Utils;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Excel
{
    const PASSWORD = 'admin';
    private $excelObj = null;

    private $_fileName = null; //文件名
    private $_fileList = []; //文件列表
    private $_path = ''; //路径

    public function getExcel()
    {
        $this->excelObj = new Spreadsheet();

        return $this->excelObj;
    }

    /**
     * 填充Excel列数据
     * param $list 数据列表
     * param $columns Excel表头数据 $columns = array(
     *   array('field' => 'name', 'title' => '姓名', 'options'=>['type'=>'']),
     *   array('field' => 'list数组元素键值', 'title' => 'B列Excel表头的标题内容'),
     * );.
     */
    public function pushData(array $list, array $columns, $title, $usePassword = true)
    {
        if (empty($columns) || empty($this->excelObj)) {
            return false;
        }

        set_time_limit(0);
        $objExcel = $this->excelObj;

        /* 设置默认文字居左，上下居中 */
        $objExcel->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $objExcel->getDefaultStyle()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $objActSheet = $objExcel->getActiveSheet();
        //默认列宽
        $objActSheet->getDefaultColumnDimension()->setWidth(15);
        $objActSheet->getDefaultRowDimension()->setRowHeight(20);

        //todo excel表头
        $i = 3;
        foreach ($columns as $key => $column) {
            $word = Coordinate::stringFromColumnIndex($key + 1);
            $objActSheet->setCellValue($word.$i, $column['title']);
            $objActSheet->getStyle($word.$i)->getFont()->setBold(true);
        }

        //todo excel内容
        foreach ($list as $info) {
            ++$i;
            foreach ($columns as $key => $column) {
                $word = Coordinate::stringFromColumnIndex($key + 1);
                $value = $info[$column['field']];

                if (isset($column['options']['data_type'])) {
                    $dataType = strtoupper($column['options']['data_type']);
                    $format = $column['options']['format'];
                    if ('IMAGE' == $dataType) { //添加图片
                        $format = in_array($format, ['online', 'local']) ? $format : 'local';
                        if ('local' == $format) { //本地图片
                            $objDrawing = new Drawing();
                            if (is_file($value)) {
                                $objDrawing->setPath($value); //写入图片路径
                            }
                        } elseif ('online' == $format) { //网络图片
                            $objDrawing = new MemoryDrawing();
                            $objDrawing->setImageResource(imagecreatefrompng($value));
                        } else {
                            continue;
                        }
                        $objDrawing->setCoordinates($word.$i); /*设置图片要插入的单元格*/
                        $objDrawing->setWidthAndHeight(100, 30);
                        $objDrawing->setWorksheet($objActSheet);
                        //设置表格宽度覆盖默认设置
                        $objActSheet->getColumnDimension($word)->setWidth(100);
                        $objActSheet->getRowDimension($i)->setRowHeight(30);
                    } elseif ('NUMBER' == $dataType) { //日期，数字，百分比，金额
                        $objActSheet->setCellValue($word.$i, $value);
                        $format = $format ? $format : NumberFormat::FORMAT_DATE_YYYYMMDDSLASH;
                        $objActSheet->getStyle($word.$i)->getNumberFormat()->setFormatCode($format);
                    } elseif ('STRING' == $dataType) {
                        $format = $format ? $format : DataType::TYPE_STRING;
                        $objActSheet->setCellValueExplicit($word.$i, $value, $format);
                    }
                } else {
                    $objActSheet->setCellValue($word.$i, $value);
                }
                //设置自动换行：前提是单元格内的值超列宽，或者在值内写入个\n
                $objActSheet->getStyle($word.$i)->getAlignment()->setWrapText(true); //自动换行
            }
        }

        //设置当前活动sheet的名称和表格标题
        //设置表格边框
        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN, // 设置border样式
                ],
            ],
        ];
        $firstWord = Coordinate::stringFromColumnIndex(1);
        $columnLength = count($columns);
        $maxWord = Coordinate::stringFromColumnIndex($columnLength);
        $objActSheet->getStyle("{$firstWord}2:{$maxWord}{$i}")->applyFromArray($border);
        $objActSheet->mergeCells("{$firstWord}2:{$maxWord}2");
        $objActSheet->setCellValue("{$firstWord}2", $title);
        $objActSheetStyle = $objActSheet->getStyle("{$firstWord}2");
        $objAlignment = $objActSheetStyle->getAlignment();
        $objAlignment->setVertical(Alignment::VERTICAL_CENTER);
        $objAlignment->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $objTitleFont = $objActSheetStyle->getFont();
        $objTitleFont->setSize(14);
        $objTitleFont->setBold(true);

        //设置保护密码
        if ($usePassword) {
            $objActSheet->getProtection()->setSheet(true);
            $objActSheet->protectCells("{$firstWord}2:{$maxWord}$i", self::PASSWORD);
        }

        return $i;
    }

    /**
     * 生成Excel文件
     * param $outputFileName Excel文件名
     * param $outputExplorer 浏览器输出或文件输出.
     */
    public function generatedFile($outputFileName, $outputExplorer = 1)
    {
        $obj = $this->excelObj;
        $obj->setActiveSheetIndex(0);
        $objWriter = new Xlsx($obj);
        if ($outputExplorer > 0) { //输出内容到浏览器
            header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
            header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
            header("Pragma:no-cache");
            header("Content-Disposition:inline;filename={$outputFileName}");
            $objWriter->save('php://output');
            exit;
        } else { //输出内容到文件通过文件路径再用Ajax无刷新页面
            $this->_fileName = $outputFileName;
            $outputFileName = $this->_path.$outputFileName;
            if (is_file($outputFileName)) {
                @unlink($outputFileName);
            }
            $this->_fileList[] = $outputFileName;
            $objWriter->save("{$outputFileName}");
        }
        $obj->disconnectWorksheets();
        unset($obj);
    }

    /**
     * 打包文件.
     *
     * @param string $fileName
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getZip($fileName = '')
    {
        $file = '';

        if (is_array($this->_fileList) && count($this->_fileList) > 0) {
            if (empty($fileName)) {
                $fileName = $this->_fileName.'.zip';
            }
            $zipName = $this->_path.$fileName;
            if (is_file($zipName)) { //先删除zip文件重新生成
                @unlink($zipName);
            }

            $zip = new \ZipArchive();
            if (true !== $zip->open($zipName, \ZipArchive::CREATE)) {
                throw new \Exception('创建压缩压缩文件失败');
            }

            //加入压缩包
            foreach ($this->_fileList as $key => $itemFilename) {
                $zip->addFile($itemFilename, basename($itemFilename));
            }
            $zip->close();
            //删除excel文件
            foreach ($this->_fileList as $itemFilename) {
                @unlink($itemFilename);
            }
            $file = $zipName;
        }

        return $file;
    }
	
	/**
     * 使用php扩展导出文件（大数据方案）
     *
     * @param $fileName
     * @param $header
     * @param $list
     * @throws \Exception
     */
    public function xlsWriter($fileName, $header, $list)
    {
        if (!extension_loaded('xlswriter')) {
            throw new \Exception('请先安装php的xlswriter扩展');
        }
        $config = ['path' => $this->_path];
        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->constMemory($fileName);

        $data = [];
        foreach ($list as $info) {
            foreach ($header as $key => $title) {
                $data[] = $info[$key];
            }
        }
        $fileObject->freezePanes(1, 0)
            ->header(array_values($header))
            ->data($data)->output();
    }
}