<?php

namespace App\Components;

/**
 * Formatter provides a set of commonly used data formatting methods.
 *
 * @author funson
 * @since 1.0
 */
class Formatter
{
    /**
     * 格式化浮点数，默认2位小数，以.分隔，千分位为空
     * @param string $value
     * @param integer $decimals
     * @param string $decimalSeparator
     * @param string $thousandSeparator
     * @return string the formatted result.
     */
    public static function asDecimal($value, $decimals = 2, $decimalSeparator = '.', $thousandSeparator = '')
    {
        return number_format($value, $decimals, $decimalSeparator, $thousandSeparator);
    }
}
