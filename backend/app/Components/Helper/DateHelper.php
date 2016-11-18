<?php
namespace App\Components\Helper;

class DateHelper
{
    /**
     * 计算天数
     * @param $startDate
     * @param $endDate
     * @return float
     */
    public static function getDays($startDate, $endDate)
    {
        return 1 + ceil((strtotime($endDate) - strtotime($startDate)) / 86400);
    }
}
