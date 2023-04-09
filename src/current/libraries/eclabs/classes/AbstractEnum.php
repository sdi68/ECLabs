<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.3
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
namespace ECLabs\Library;

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;


/**
 * Абстрактный класс нумератора
 * @package ECLabs\Library
 * @version 1.0.0
 * @since 1.0.0
 */
abstract class AbstractEnum
{
    /**
     * @var array Массив принимаемых enum'ом значений
     * @since 1.0.0
     */
    protected static $validValues = array();

    /**
     * Проверяет наличие значения в enum'e
     * @param mixed $value Проверяемое значение
     * @return bool True если значение имеется, false если нет
     * @since 1.0.0
     */
    public static function valueExists($value)
    {
        $value = self::getEnumName($value);
        return array_key_exists($value, static::$validValues);
    }

    /**
     * Возвращает все значения в enum'e
     * @return array Массив значений в перечислении
     * @since 1.0.0
     */
    public static function getValidValues()
    {
        return array_keys(static::$validValues);
    }


    /**
     * Возвращает значения в enum'е значения которых разрешены
     * @return string[] Массив разрешённых значений
     * @since 1.0.0
     */
    public static function getEnabledValues()
    {
        $result = array();
        foreach (static::$validValues as $key => $enabled) {
            if ($enabled) {
                $result[] = $key;
            }
        }
        return $result;
    }

    /**
     * Возвращает массив значений нумератора
     * имя и значение
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function getEnums()
    {
        $result = array();
        foreach (static::$validValues as $key => $enabled) {
            if ($enabled) {
                $result[] = array('name' => $key, 'value' => constant('static::' . $key));
            }
        }
        return $result;
    }

    /**
     * Получает наименование нумератора по значению
     * @param $value
     * @return false|int|string
     * @since 1.0.0
     */
    public static function getEnumName($value)
    {
        foreach (static::$validValues as $key => $val) {
            if ($value == constant('static::' . $key)) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Получает перевод наименования нумератора по значению
     * @param string $value
     * @return string
     * @since 1.0.0
     */
    public static function getEnumNameText(string $value): string
    {
        return Text::_(self::getEnumName($value) . '_TEXT');
    }
}
