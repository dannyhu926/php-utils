<?php
/**
 *
 * @package   Utils
 * @license   MIT
 * @copyright Copyright (C) JBZoo.com,  All rights reserved.
 * @link      https://github.com/JBZoo/Utils
 * @author   hudy <469671292@163.com>
 */

namespace Utils;

use \DateTime;
use \DateTimeZone;

/**
 * Class Dates
 * @package JBZoo\Utils
 */
class Dates
{
    const MINUTE = 60;
    const HOUR   = 3600;
    const DAY    = 86400;
    const WEEK   = 604800;   // 7 days
    const MONTH  = 2592000;  // 30 days
    const YEAR   = 31536000; // 365 days

    const SQL_FORMAT = 'Y-m-d H:i:s';
    const SQL_NULL   = '0000-00-00 00:00:00';

    /**
     * Convert to timestamp 获得时间戳
     *
     * @param string|DateTime $time
     * @param bool $currentIsDefault
     * @return int
     */
    public static function toStamp($time = null, $currentIsDefault = true) {
        if ($time instanceof DateTime) {
            return $time->format('U');
        }

        if (!empty($time)) {
            if (is_numeric($time)) {
                $time = (int)$time;
            } else {
                $time = strtotime($time);
            }
        }

        if (!$time) {
            if ($currentIsDefault) {
                $time = time();
            } else {
                $time = 0;
            }
        }

        return $time;
    }

    /**
     * @param mixed $time
     * @param null $timeZone
     * @return DateTime
     */
    public static function factory($time = null, $timeZone = null) {
        $timeZone = self::timezone($timeZone);

        if ($time instanceof DateTime) {
            return $time->setTimezone($timeZone);
        }

        $dateTime = new DateTime('@' . self::toStamp($time));
        $dateTime->setTimezone($timeZone);

        return $dateTime;
    }

    /**
     * Return a DateTimeZone object based on the current timezone.获取或者设置时区
     *
     * @param mixed $timezone
     * @return \DateTimeZone
     */
    public static function timezone($timezone = null) {
        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }

        $timezone = $timezone ? : date_default_timezone_get();

        return new DateTimeZone($timezone);
    }

    /**
     * Convert time for sql format
     *
     * @param null|int $time
     * @return string
     */
    public static function sql($time = null) {
        return self::factory($time)->format(self::SQL_FORMAT);
    }

    /**
     * 格式化输出
     * @param string|int $date
     * @param string $format
     * @return string
     */
    public static function human($date = '', $format = 'd M Y H:i') {
        return self::factory($date)->format($format);
    }

    /**
     * Returns true if date passed is within this week.
     *
     * @param string|int $time
     * @return bool
     */
    public static function isThisWeek($time) {
        return (self::factory($time)->format('W-Y') === self::factory()->format('W-Y'));
    }

    /**
     * Returns true if date passed is within this month.
     *
     * @param string|int $time
     * @return bool
     */
    public static function isThisMonth($time) {
        return (self::factory($time)->format('m-Y') === self::factory()->format('m-Y'));
    }

    /**
     * Returns true if date passed is within this year.
     *
     * @param string|int $time
     * @return bool
     */
    public static function isThisYear($time) {
        return (self::factory($time)->format('Y') === self::factory()->format('Y'));
    }

    /**
     * Returns true if date passed is tomorrow.
     *
     * @param string|int $time
     * @return bool
     */
    public static function isTomorrow($time) {
        return (self::factory($time)->format('Y-m-d') === self::factory('tomorrow')->format('Y-m-d'));
    }

    /**
     * Returns true if date passed is today.
     *
     * @param string|int $time
     * @return bool
     */
    public static function isToday($time) {
        return (self::factory($time)->format('Y-m-d') === self::factory()->format('Y-m-d'));
    }

    /**
     * Returns true if date passed was yesterday.
     *
     * @param string|int $time
     * @return bool
     */
    public static function isYesterday($time) {
        return (self::factory($time)->format('Y-m-d') === self::factory('yesterday')->format('Y-m-d'));
    }

    /**
     * 检查年、月、日是有效组合。
     * @param integer $y year
     * @param integer $m month
     * @param integer $d day
     * @return boolean true if valid date, semantic check only.
     */
    public static function isValidDate($y, $m, $d) {
        return checkdate($m, $d, $y);
    }

    /**
     * 检查日期是否合法日期。
     * @param string $date 2012-1-12
     * @param string $separator
     * @return boolean true if valid date, semantic check only.
     */
    public static function checkDate($date, $separator = "-") {
        $dateArr = explode($separator, $date);
        return self::isValidDate($dateArr[0], $dateArr[1], $dateArr[2]);
    }

    /**
     * 检查是否有效的小时、分钟和秒.
     * @param integer $h hour
     * @param integer $m minute
     * @param integer $s second
     * @param boolean $hs24 whether the hours should be 0 through 23 (default) or 1 through 12.
     * @return boolean true if valid date, semantic check only.
     */
    public static function isValidTime($h, $m, $s, $hs24 = true) {
        if ($hs24 && ($h < 0 || $h > 23) || !$hs24 && ($h < 1 || $h > 12)) return false;
        if ($m > 59 || $m < 0) return false;
        if ($s > 59 || $s < 0) return false;
        return true;
    }

    /**
     * 检查时间是否合法时间
     * @param integer $time
     * @param string $separator
     * @return boolean true if valid date, semantic check only.
     * @since 1.0.5
     */
    public static function checkTime($time, $separator = ":") {
        $timeArr = explode($separator, $time);
        return self::isValidTime($timeArr[0], $timeArr[1], $timeArr[2]);
    }

    /**
     * 获取星期
     *
     * @param string $date 日期
     * @return int
     */
    public static function getWeekNum($date, $separator = "-") {
        $dateArr = explode($separator, $date);
        return date("w", mktime(0, 0, 0, $dateArr[1], $dateArr[2], $dateArr[0]));
    }

    /**
     * 获取星期
     *
     * @param int $week 星期，默认为当前时间获取
     * @return string
     */
    public static function getWeek($week = null) {
        $week = $week ? $week : self::human(null, 'w');
        $weekArr = array('星期天', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六');
        return $weekArr[$week];
    }

    /**
     * 判断是否为闰年
     *
     * @param int $year 年份，默认为当前年份
     * @return bool
     */
    public static function isLeapYear($year = null) {
        $year = $year ? $year : self::human(null, 'Y');
        return ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0);
    }

    /**
     * 获取一年中有多少天
     * @param int $year 年份，默认为当前年份
     */
    public static function getDaysInYear($year = null) {
        $year = $year ? $year : self::human(null, 'Y');
        return self::isLeapYear($year) ? 366 : 365;
    }

    /**
     * 获取一天中的时段
     *
     * @param int $hour 小时，默认为当前小时
     * @return string
     */
    public static function getPeriodOfTime($hour = null) {
        $hour = $hour ? $hour : self::human(null, 'G');
        $period = null;
        if ($hour >= 0 && $hour < 6) {
            $period = '凌晨';
        } elseif ($hour >= 6 && $hour < 8) {
            $period = '早上';
        } elseif ($hour >= 8 && $hour < 11) {
            $period = '上午';
        } elseif ($hour >= 11 && $hour < 13) {
            $period = '中午';
        } elseif ($hour >= 13 && $hour < 15) {
            $period = '响午';
        } elseif ($hour >= 15 && $hour < 18) {
            $period = '下午';
        } elseif ($hour >= 18 && $hour < 20) {
            $period = '傍晚';
        } elseif ($hour >= 20 && $hour < 22) {
            $period = '晚上';
        } elseif ($hour >= 22 && $hour <= 23) {
            $period = '深夜';
        }
        return $period;
    }

    public static function timeFromNow($dateline) {
        if (empty($dateline)) return false;
        $seconds = time() - $dateline;
        if ($seconds < 60) {
            return "1分钟前";
        } elseif ($seconds < 3600) {
            return floor($seconds / 60) . "分钟前";
        } elseif ($seconds < 24 * 3600) {
            return floor($seconds / 3600) . "小时前";
        } elseif ($seconds < 48 * 3600) {
            return date("昨天 H:i", $dateline) . "";
        } else {
            return date('Y-m-d', $dateline);
        }
    }

    /**
     * 日期数字转中文，适用于日、月、周
     * @param int $day 日期数字，默认为当前日期
     * @return string
     */
    public static function numberToChinese($number) {
        $chineseArr = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
        $chineseStr = null;
        if ($number < 10) {
            $chineseStr = $chineseArr[$number - 1];
        } elseif ($number < 20) {
            $chineseStr = '十' . $chineseArr[$number - 11];
        } elseif ($number < 30) {
            $chineseStr = '二十' . $chineseArr[$number - 21];
        } else {
            $chineseStr = '三十' . $chineseArr[$number - 31];
        }
        return $chineseStr;
    }

    /**
     * 年份数字转中文
     *
     * @param int $year 年份数字，默认为当前年份
     * @return string
     */
    public static function yearToChinese($year = null, $flag = false) {
        $year = $year ? intval($year) : self::human(null, 'Y');
        $data = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $chineseStr = null;
        for ($i = 0; $i < 4; $i++) {
            $chineseStr .= $data[substr($year, $i, 1)];
        }
        return $flag ? '公元' . $chineseStr : $chineseStr;
    }


    /**
     * 获取日期所属的星座、干支、生肖
     *
     * @param string $type 获取信息类型（SX：生肖、GZ：干支、XZ：星座）
     * @return string
     */
    public static function dateInfo($type, $date = null) {
        $year = self::human($date, 'Y');
        $month = self::human($date, 'm');
        $day = self::human($date, 'd');
        $result = null;
        switch ($type) {
            case 'SX':
                $data = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
                $result = $data[($year - 4) % 12];
                break;
            case 'GZ':
                $data = array(
                    array('甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'),
                    array('子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥')
                );
                $num = $year - 1900 + 36;
                $result = $data[0][$num % 10] . $data[1][$num % 12];
                break;
            case 'XZ':
                $data = array('摩羯', '宝瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
                $zone = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
                if ((100 * $month + $day) >= $zone[0] || (100 * $month + $day) < $zone[1]) {
                    $i = 0;
                } else {
                    for ($i = 1; $i < 12; $i++) {
                        if ((100 * $month + $day) >= $zone[$i] && (100 * $month + $day) < $zone[$i + 1]) break;
                    }
                }
                $result = $data[$i] . '座';
                break;
        }
        return $result;
    }


    /**
     * 获取两个日期的差
     *
     * @param string $interval 日期差的间隔类型，（Y：年、M：月、W：星期、D：日期、H：时、N：分、S：秒）
     * @param int $startDateTime 开始日期
     * @param int $endDateTime 结束日期
     * @return int
     */
    public static function dateDiff($interval, $startDateTime, $endDateTime) {
        $diff = self::toStamp($endDateTime) - self::toStamp($startDateTime);
        switch ($interval) {
            case 'Y': //年
                $result = bcdiv($diff, 60 * 60 * 24 * 365);
                break;
            case 'M': //月
                $result = bcdiv($diff, 60 * 60 * 24 * 30);
                break;
            case 'W': //星期
                $result = bcdiv($diff, 60 * 60 * 24 * 7);
                break;
            case 'D': //日
                $result = bcdiv($diff, 60 * 60 * 24);
                break;
            case 'H': //时
                $result = bcdiv($diff, 60 * 60);
                break;
            case 'N': //分
                $result = bcdiv($diff, 60);
                break;
            case 'S': //秒
            default:
                $result = $diff;
                break;
        }
        return $result;
    }


    /**
     * 返回指定日期在一段时间间隔时间后的日期
     *
     * @param string $interval 时间间隔类型，（Y：年、Q：季度、M：月、W：星期、D：日期、H：时、N：分、S：秒）
     * @param int $value 时间间隔数值，数值为正数获取未来的时间，数值为负数获取过去的时间
     * @param string $dateTime 日期
     * @param string $format 返回的日期转换格式
     * @return string 返回追加后的日期
     */
    public static function dateAdd($interval, $value, $dateTime = null, $format = null) {
        $dateTime = $dateTime ? $dateTime : self::human();
        $date = getdate(self::toStamp($dateTime));
        switch ($interval) {
            case 'Y': //年
                $date['year'] += $value;
                break;
            case 'Q': //季度
                $date['mon'] += ($value * 3);
                break;
            case 'M': //月
                $date['mon'] += $value;
                break;
            case 'W': //星期
                $date['mday'] += ($value * 7);
                break;
            case 'D': //日
                $date['mday'] += $value;
                break;
            case 'H': //时
                $date['hours'] += $value;
                break;
            case 'N': //分
                $date['minutes'] += $value;
                break;
            case 'S': //秒
            default:
                $date['seconds'] += $value;
                break;
        }
        return self::human(mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']), $format);
    }


    /**
     * 根据年份获取每个月的天数
     *
     * @param int $year 年份
     * @return array 月份天数数组
     */
    public static function getDaysByMonthsOfYear($year = null) {
        $year = $year ? $year : self::human(null, 'Y');
        $months = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        if (self::isLeapYear($year)) $months[1] = 29;
        return $months;
    }


    /**
     * 返回某年的某个月有多少天
     *
     * @param int $month 月份
     * @param int $year 年份
     * @return int 月份天数
     */
    public static function getDaysByMonth($month, $year) {
        $months = self::getDaysByMonthsOfYear($year);
        $value = $months[$month - 1];
        return !$value ? 0 : $value;
    }


    /**
     * 获取年份的第一天
     *
     * @param int $year 年份
     * @param int $format 返回的日期格式
     * @return string 返回的日期
     */
    public static function firstDayOfYear($year = null, $format = 'Y-m-d') {
        $year = $year ? $year : self::human(null, 'Y');
        return self::human(mktime(0, 0, 0, 1, 1, $year), $format);
    }


    /**
     * 获取年份最后一天
     *
     * @param int $year 年份
     * @param int $format 返回的日期格式
     * @return string 返回的日期
     */
    public static function lastDayOfYear($year = null, $format = 'Y-m-d') {
        $year = $year ? $year : self::human(null, 'Y');
        return self::human(mktime(0, 0, 0, 1, 0, $year + 1), $format);
    }


    /**
     * 获取月份的第一天
     *
     * @param int $month 月份
     * @param int $year 年份
     * @param int $format 返回的日期格式
     * @return string 返回的日期
     */
    public static function firstDayOfMonth($month = null, $year = null, $format = 'Y-m-d') {
        $year = $year ? $year : self::human(null, 'Y');
        $month = $month ? $month : self::human(null, 'm');
        return self::human(mktime(0, 0, 0, $month, 1, $year), $format);
    }


    /**
     * 获取月份最后一天
     *
     * @param int $month 月份
     * @param int $year 年份
     * @param int $format 返回的日期格式
     * @return string 返回的日期
     */
    public static function lastDayOfMonth($month = null, $year = null, $format = 'Y-m-d') {
        $year = $year ? $year : self::human(null, 'Y');
        $month = $month ? $month : self::human(null, 'm');
        return self::human(mktime(0, 0, 0, $month + 1, 0, $year), $format);
    }


    /**
     * 获取两个日期之间范围
     *
     * @param string $startDateTime
     * @param string $endDateTime
     * @param string $format
     * @return array 返回日期数组
     */
    public static function getDayRangeInBetweenDate($startDateTime, $endDateTime, $sort = false, $format = 'Y-m-d') {
        $startDateTime = self::toStamp($startDateTime);
        $endDateTime = self::toStamp($endDateTime);
        $num = ($endDateTime - $startDateTime) / 86400;
        $dateArr = array();
        for ($i = 0; $i <= $num; $i++) {
            $dateArr[] = self::human($startDateTime + 86400 * $i, $format);
        }
        return $sort ? array_reverse($dateArr) : $dateArr;
    }
}
