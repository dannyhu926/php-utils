<?php
namespace Utils;
/**
 * @author        dannyhu
 * Excel          导出导入数据类
 * $excelUtils =  Excel::getInstance();
 * $objPHPExcel = Excel::createObjPHPExcel();
 */
class Excel
{
    private static $_instanceObj = null;
    private static $_instanceExcelObj = null;
    private static $filesuffix2excel = array(
        'xls' => 'Excel5',
        'xlsx' => 'Excel2007',
        'pdf' => 'PDF',
        'csv' => 'CSV',
    );

    public function __construct() {
        /*导入phpExcel核心类  */
        include_once('Excel/PHPExcel.php');
        include_once('Excel/PHPExcel/Writer/Excel2007.php'); //用于其他低版本xls
        include_once('Excel/PHPExcel/Writer/Excel5.php'); //用于2007格式
        include_once('Excel/PHPExcel/IOFactory.php');
    }

    public static function getInstance() {
        if (self::$_instanceObj == null) {
            self::$_instanceObj = new self();
        }
        return self::$_instanceObj;
    }

    //得到Excel对象
    public static function createObjPHPExcel() {
        if (self::$_instanceExcelObj == null) {
            self::$_instanceExcelObj = new PHPExcel();
        }
        return self::$_instanceExcelObj;
    }

    /**
     * 生成Excel文件
     * param $outputFileName Excel文件名
     * param $outputExplorer 浏览器输出或文件输出
     */
    public function generatedFile($outputFileName, $outputExplorer = 1) {
        $obj = self::createObjPHPExcel();

        $obj->setActiveSheetIndex(0);
        //页眉页脚
        $obj->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $obj->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $obj->getProperties()->getTitle() . '&RPage &P of &N');

        // 设置页方向和规模
        $obj->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $obj->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        header('Content-Type: application/vnd.ms-excel;charset=UTF-8');
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Pragma:no-cache");
        header("Content-Disposition:inline;filename={$outputFileName}");
        $excel_type = PHPExcel_IOFactory::identify($outputFileName);
        $objWriter = PHPExcel_IOFactory::createWriter($obj, $excel_type);

        if ($outputExplorer > 0) { //输出内容到浏览器
            $objWriter->save('php://output');
        } else { //输出内容到文件通过文件路径再用Ajax无刷新页面
            $objWriter->save("{$outputFileName}");
        }
        exit;
    }

    /**
     * 读取Excel的数据
     * param $callback 对数据进行操作的回调方法名
     * param $filename 读取excel路径文件名
     */
    public function readData($filename, $sheetIndex = 1) {
        $reader_type = PHPExcel_IOFactory::identify($filename);
        $objReader = PHPExcel_IOFactory::createReader($reader_type);
        $objReader->setReadDataOnly(true); //只读取数据，忽略里面各种格式等(对于Excel读去，有很大优化)
        $objPHPExcel = $objReader->load($filename);
        $sheetIdx = max((int)$sheetIndex - 1, 0);
        $objWorksheet = $objPHPExcel->getSheet($sheetIdx);

        $highestRow = $objWorksheet->getHighestRow(); // 取得总行数
        $highestColumn = $objWorksheet->getHighestColumn(); // 取得总列数
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row][] = (string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
        return $excelData;
    }

    /**
     * 读取Excel的数据并对数据进行操作：如入库，校验等
     * param $callback 对数据进行操作的回调方法名
     * param $filename 读取excel路径文件名
     * param $reader_type 读取excel的类型
     */
    public function readDataCallBack($filename, callable $callback, $sheetIndex = 1) {
        $reader_type = PHPExcel_IOFactory::identify($filename);
        $reader = PHPExcel_IOFactory::createReader($reader_type);
        $PHPExcel = $reader->load($filename); // 档案名称
        $sheetIdx = max((int)$sheetIndex - 1, 0);
        $sheet = $PHPExcel->getSheet($sheetIdx);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = $sheet->getHighestColumn(); // 取得总列数
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = array();
            for ($column = 0; $column <= $highestColumnIndex; $column++) {
                $cell = $sheet->getCellByColumnAndRow($column, $row);
                $value = $cell->getValue();
                //todo 关于日期判断的部分主要是以下部
                if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                    $cellstyleformat = $cell->getParent()->getStyle($cell->getCoordinate())->getNumberFormat();
                    $formatcode = $cellstyleformat->getFormatCode();
                    if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
                        $value = gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
                    } else {
                        $value = PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
                    }
                }
                $rowData[$column] = $value;
            }
            call_user_func($callback, $rowData);
        }
    }

    /**
     * 读取excel转换成数组
     *
     * @param string $excelFile 文件路径
     * @param int $startRow 开始读取的行数
     * @param int $endRow 读取的条数
     * @return array
     */
    public function readFilterData($excelFile, $startRow = 2, $chunkSize = 600, $sheetIndex = 1) {
        $excelType = PHPExcel_IOFactory::identify($excelFile);
        $excelReader = PHPExcel_IOFactory::createReader($excelType);
        $chunkFilter = new ChunkReadFilter($startRow, $chunkSize);
        $excelReader->setReadFilter($chunkFilter); // 设置实例化的过滤器对象
        $phpexcel = $excelReader->load($excelFile);
        $sheetIdx = max((int)$sheetIndex - 1, 0);
        $activeSheet = $phpexcel->getSheet($sheetIdx);

        $highestColumn = $activeSheet->getHighestColumn(); //最后列数所对应的字母，例如第1行就是A
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        $data = array();
        $endRow = $startRow + $chunkSize;
        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $cell = $activeSheet->getCellByColumnAndRow($col, $row);
                $value = $cell->getValue();
                //todo 关于日期判断的部分主要是以下部
                if ($cell->getDataType() == PHPExcel_Cell_DataType::TYPE_NUMERIC) {
                    $cellstyleformat = $cell->getParent()->getStyle($cell->getCoordinate())->getNumberFormat();
                    $formatcode = $cellstyleformat->getFormatCode();
                    if (preg_match('/^(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy]/i', $formatcode)) {
                        $value = gmdate("Y-m-d", PHPExcel_Shared_Date::ExcelToPHP($value));
                    } else {
                        $value = PHPExcel_Style_NumberFormat::toFormattedString($value, $formatcode);
                    }
                }
                $data[$row][] = $value;
            }
        }
        return $data;
    }

    /**
     * 填充Excel列数据
     * param $list 数据列表
     * param $columns Excel表头数据 $columns = array(
     *   array('field' => 'name', 'title' => '姓名', 'data_type'=>'string','format'=>PHPExcel_Cell_DataType::TYPE_STRING),
     *   array('field' => 'list数组元素键值', 'title' => 'B列Excel表头的标题内容'),
     * );
     */
    public function pushData(Array $list, Array $columns, $sheet_title) {
        if (empty($columns)) return false;
        $objExcel = self::createObjPHPExcel();
        $objActSheet = $objExcel->getActiveSheet();

        //todo excel表头
        $i = 3;
        foreach ($columns as $key => $column) {
            $word = PHPExcel_Cell::stringFromColumnIndex($key + 1);
            $objActSheet->setCellValue($word . $i, $column['title']);
        }

        //todo excel内容
        foreach ($list as $info) {
            $i++;
            foreach ($columns as $key => $column) {
                $word = PHPExcel_Cell::stringFromColumnIndex($key + 1);
                $value = $info[$column['field']];

                if (isset($column['data_type'])) {
                    $data_type = strtoupper($column['data_type']);
                    if ($data_type == 'IMAGE') { //添加图片
                        $format = in_array($column['source'], ['online', 'local']) ? $column['source'] : 'local';
                        if ($format == 'local') { //本地图片
                            $objDrawing = new PHPExcel_Worksheet_Drawing();
                            if (is_file($value)) {
                                $objDrawing->setPath($value); //写入图片路径
                            }
                        } elseif ($format == 'online') { //网络图片
                            $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
                            $objDrawing->setImageResource(imagecreatefrompng($value));
                        } else {
                            continue;
                        }
                        $objDrawing->setCoordinates($word . $i); /*设置图片要插入的单元格*/
                        $objDrawing->setWidthAndHeight(100, 30);
                        $objDrawing->setWorksheet($objActSheet);
                        //设置表格宽度覆盖默认设置
                        $objActSheet->getColumnDimension($word)->setWidth(100);
                        $objActSheet->getRowDimension($i)->setRowHeight(30);
                    } elseif ($data_type == 'NUMBER') { //日期，数字，百分比，金额
                        $objActSheet->setCellValue($word . $i, $value);
                        $format = isset($column['format']) ? $column['format'] : PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDDSLASH;
                        $objActSheet->getStyle($word . $i)->getNumberFormat()->setFormatCode($format);
                    } elseif ($data_type == 'STRING') {
                        $format = isset($column['format']) ? $column['format'] : PHPExcel_Cell_DataType::TYPE_STRING;
                        $objActSheet->setCellValueExplicit($word . $i, $value, $format);
                    }
                } else {
                    $objActSheet->setCellValue($word . $i, $value);
                }
                //设置自动换行：前提是单元格内的值超列宽，或者在值内写入个\n
                $objActSheet->getStyle($word . $i)->getAlignment()->setWrapText(true); //自动换行
            }
        }

        //设置表格边框
        $style_obj = new PHPExcel_Style();
        $style_array = array(
            'borders' => array( //上下左右画线
                'top' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'left' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'bottom' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                'right' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
            )
        );
        $style_obj->applyFromArray($style_array);
        $firstWord = PHPExcel_Cell::stringFromColumnIndex(1);
        $column_length = count($columns);
        $maxWord = PHPExcel_Cell::stringFromColumnIndex($column_length);
        $objActSheet->setSharedStyle($style_obj, "{$firstWord}2:{$maxWord}{$i}");

        //默认列宽
        $objActSheet->getDefaultColumnDimension()->setWidth(20);

        //设置当前活动sheet的名称和表格标题
        $objActSheet->setTitle($sheet_title);
        $objActSheet->mergeCells("{$firstWord}2:{$maxWord}2");
        $objActSheet->setCellValue("{$firstWord}2", $sheet_title);
        $objActSheetStyle = $objActSheet->getStyle("{$firstWord}2");
        $objAlignment = $objActSheetStyle->getAlignment();
        $objAlignment->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objAlignment->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objTitleFont = $objActSheetStyle->getFont();
        $objTitleFont->setSize(14);
        $objTitleFont->setBold(true);
    }
}

/**
 * 读取excel过滤器类 单独文件
 */
class ChunkReadFilter implements PHPExcel_Reader_IReadFilter
{
    private $_startRow = 0; // 开始行
    private $_endRow = 0; // 结束行
    private $_columns = []; // 列跨度
    public function __construct($startRow, $chunkSize, $columns = []) {
        $this->_startRow = $startRow;
        $this->_endRow = $startRow + $chunkSize;
        $this->_columns = $columns;
    }

    public function readCell($column, $row, $worksheetName = '') {
        if ($row >= $this->_startRow && $row <= $this->_endRow) { //过滤行
//            if (in_array($column,range('A','E'))) {
            if ($this->_columns && in_array($column, $this->_columns)) { //过滤列
                return true;
            }
        }
        return false;
    }
}
