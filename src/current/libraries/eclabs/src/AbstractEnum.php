<?php
/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Абстрактный класс нумератора
 * @package ECLabs\Library
 * @version 1.0.0
 * @since   1.0.0
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
	 *
	 * @param   mixed  $value  Проверяемое значение
	 *
	 * @return bool True если значение имеется, false если нет
	 * @since 1.0.0
	 */
	public static function valueExists(mixed $value): bool
	{
		$value = self::getEnumName($value);

		return array_key_exists($value, static::$validValues);
	}

	/**
	 * Получает наименование нумератора по значению
	 *
	 * @param $value
	 *
	 * @return false|int|string
	 * @since 1.0.0
	 */
	public static function getEnumName($value)
	{
		foreach (static::$validValues as $key => $val)
		{
			if ($value == constant('static::' . $key))
			{
				return $key;
			}
		}

		return false;
	}

	/**
	 * Возвращает все значения в enum'e
	 * @return array Массив значений в перечислении
	 * @since 1.0.0
	 */
	public static function getValidValues(): array
	{
		return array_keys(static::$validValues);
	}

	/**
	 * Возвращает значения в enum'е значения которых разрешены
	 * @return string[] Массив разрешённых значений
	 * @since 1.0.0
	 */
	public static function getEnabledValues(): array
	{
		$result = array();
		foreach (static::$validValues as $key => $enabled)
		{
			if ($enabled)
			{
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
	public static function getEnums(): array
	{
		$result = array();
		foreach (static::$validValues as $key => $enabled)
		{
			if ($enabled)
			{
				$result[] = array('name' => $key, 'value' => constant('static::' . $key));
			}
		}

		return $result;
	}

	/**
	 * Получает перевод наименования нумератора по значению
	 *
	 * @param   string  $value    Значение нумератора
	 * @param   string  $unknown  Текст для отсутствующего значения
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public static function getEnumNameText(string $value, string $unknown = "ECL_UNKNOWN_ENUM"): string
	{
		//return Text::_(self::getEnumName($value)? . '_TEXT');
		$text = self::getEnumName($value);
		if (!$text)
		{
			return $unknown;
		}
		else
		{
			return Text::_($text . '_TEXT');
		}
	}
}
