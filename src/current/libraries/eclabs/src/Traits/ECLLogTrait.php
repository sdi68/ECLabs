<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Traits;
\defined('_JEXEC') or die;

use ECLabs\Library\ECLLogging\ECLLogging;

/**
 * Трайт логирования. Формирует лог сущности в отдельном файле.
 * @since 2.0.0
 */
trait ECLLogTrait
{
	/**
	 * Имя сущности, которая создает файл лога.
	 * @var string
	 * @since 2.0.0
	 */
	protected string $_entity_name = "";

	/**
	 * Флаг разрешения ведения лога.
	 * @var bool
	 * @since 2.0.0
	 */
	protected bool $_enable_logging;

	/**
	 * Логирование работы плагина
	 *
	 * @param   string  $type     Тип записи
	 * @param   string  $message  Сообщение для записи в лог (например имя переменной)
	 * @param   array   $data     Переменная для записи в лог
	 *
	 * @return void
	 * @see   ECLLOG
	 * @since 2.0.0
	 */
	protected final function _addLog(string $type, string $message, mixed $data = null): void
	{
		ECLLogging::add(
			array(
				"source"           => $this->_entity_name,
				"enabled"          => $this->_enable_logging,
				"logger"           => "ECLabs\\Library\\ECLLogging\\Loggers\\ECLabDefaultLogger",
				"back_trace_level" => 3
			),
			array(
				"timestamp" => "",
				"type"      => $type,
				"caller"    => "",
				"message"   => $message,
				"data"      => $data,
			)
		);
	}

}