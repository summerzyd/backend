<?php
namespace App\Components\Symbol;

class SymbolFactory
{
    /**
     * 实例化类
     * @param $appClass
     * @return mixed
     */
    public static function getClass($appClass)
    {
        //首字母大写
        $appClass = '\App\Components\Symbol\\' . ucfirst(strtolower($appClass));
        return new $appClass;
    }
}
