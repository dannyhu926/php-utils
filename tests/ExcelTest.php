<?php

namespace JBZoo\PHPUnit;

class ExcelTest extends PHPUnit
{
    public function readBigExcel() {
        $chunkSize = 200;
        $startRow = 2; //从第二行开始读取
        $excelFile = 'D:/test.xls';
        $column_arr = ['id', 'phone', 'province', 'city', 'operators', 'area_code', 'post_code'];
        while (true) {
            $data = Excel::readFilterData($excelFile, $column_arr, $startRow, $chunkSize);
            if (empty($data)) {
                break;
            }
            //todo logic here
            $startRow = $startRow + $chunkSize + 1;
        }
    }
} 