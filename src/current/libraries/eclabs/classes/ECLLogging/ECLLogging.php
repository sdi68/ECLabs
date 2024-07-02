<?php
/**
 * @package             Econsult Labs Library
 * @version             1.0.20
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2024 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @since               1.0.20
 */

namespace ECLabs\Library\ECLLogging;

use ECLabs\Library\ECLLogging\Loggers\ECLabLogger;
use ECLLOG\ECLLOG;


/**
 * Класс создания логов для отдельных компонентов
 * @package     ECLabs\Library\ECLLogging
 *
 * @since       1.0.20
 */
class ECLLogging extends ECLLOG
{

	/**
	 * Добавляет запись в лог
	 *
	 * @param array $options Настройки логирования
	 * $options['enabled'] - bool - разрешена или нет запись в лог
	 * $options['source'] - string - наименование компонента источника лога
	 * $options['logger'] - string - класс логгера (с пространством имен)
	 * $options['back_trace_level'] - int - число уровней в debug_backtrace от функции, создающей запись в логе до текущей функции
	 * @param array $data Данные для записи в лог
	 * --- Обязательные поля ---
	 * $data['timestamp'] - string метка времени или пустое значение, чтоб сгенерировать в логгере
	 * $data['type'] - string тип записи из перечня ECLLOG::INFO, ECLLOG::ERROR, ECLLOG::WARNING
	 * $data['caller'] - string вызывающая запись процедура или если пустое, то определяется через debug_backtrace
	 * $data['message'] - string сообщение для вывода в лог
	 * $data['data'] - mixed значение переменной для вывода в лог
	 * --- Дополнительные поля ---
	 * Определяются для конкретного типа логгера
	 *
	 * @return void
	 * @see ECLLOG::_add()
	 *
	 * @since   1.0.20
	 */
	public static function add(array $options, array $data = null): void
	{
		if (self::_checkLogEnabled($options))
		{
			if (!isset(self::$loggers[$options['source']]))
			{
				self::$loggers[$options['source']] = new $options['logger']($options);
			}
			self::_add($options, $data);
		}
	}

}
