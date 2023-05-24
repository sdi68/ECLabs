<?php
/*
 * ECLClassLoader.php  23.05.2023, 15:12
 * Created for project Joomla 3.x
 * Subpackage ___
 * www.econsultlab.ru
 * mail: info@econsultlab.ru
 * Released under the GNU General Public License
 * Copyright (c) 2023 Econsult Lab.
 */

namespace ECLabs\Library;

/**
 * Namespace classes loader
 * @package     ECLabs\Library
 *
 * @since 1.0.7
 */
class ECLAutoLoader
{
    /**
     * Пакетная загрузка пространств имен
     *
     * @param array $params Параметры пространств имен. Массив каждый элемент содержит массив из
     * classNameSpace - Наименование пространства имен
     * classRootPath - Путь к папке с классами пространства имен
     *
     *
     * @since 1.0.7
     */
    public static function autoloadBatch(array $params ): void {
        foreach ($params as $param) {
            if(isset($param['classNameSpace']) && $param['classNameSpace'] &&
                isset($param['classRootPath']) && $param['classRootPath']
            ) {
                self::autoload($param['classNameSpace'],$param['classRootPath']);
            }
        }
    }

    /**
     * Инициализация загрузчика классов
     * @param string $classNameSpace    Наименование пространства имен
     * @param string $classRootPath     Путь к папке с классами пространства имен
     *
     *
     * @since 1.0.7
     */
    public static function autoload(string $classNameSpace, string $classRootPath=""): void
    {
        $_classNameSpace = $classNameSpace;
        $_classRootPath=$classRootPath?? dirname(__FILE__);
        spl_autoload_register(function($className) use ($_classRootPath, $_classNameSpace) {
            $length  = strlen($_classNameSpace);
            if (strncmp($_classNameSpace, $className, $length) === 0)
            {
                $path = $_classRootPath;
            }
            else
            {
                return;
            }
            $path .= str_replace('\\', '/', substr($className, $length)) . '.php';

            if (file_exists($path))
            {
                require $path;
            }
        });
    }
}