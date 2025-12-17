<?php
/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Traits;

\defined('_JEXEC') or die;

use ECLabs\Library\ECLLogging\ECLLogging;
use ECLabs\Library\ECLVersion;
use ECLLOG\ECLLOG;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;

trait ECLPlugin
{
	/**
	 * Путь до файла плагина коннектора
	 * Должна быть инициализирована в самом коннекторе
	 * @var string
	 * @since 1.0.0
	 */
	protected $_plugin_path = "";

	/**
	 * Флаг разрешения логирования работы плагина
	 * @var bool
	 * @since 1.0.0
	 */
	protected $enabled_log = false;

	/**
	 * Версия Joomla
	 * @var string
	 * @since 1.0.0
	 */
	protected $jVersion = "3";

	/**
	 * Подписка на события для Joomla 4
	 *
	 * @return string[]
	 *
	 * @deprecated @since 2.0.0
	 *
	 * @since      1.0.16
	 */
	public static function getSubscribedEvents(): array
	{
		return array();
	}

	/**
	 * Подключаем медиа конкретного плагина
	 * @return void
	 * @since 1.0.0
	 */
	abstract protected function _addMedia(): void;

	/**
	 * Builds the layout to be shown, along with hidden fields.
	 *
	 * @param   object  $vars    Data from component
	 * @param   string  $layout  Layout name
	 *
	 * @return   string  Layout html
	 * @throws Exception
	 * @since   1.0.0
	 *
	 */
	protected final function _buildLayout(object $vars, string $layout = 'default'): string
	{
		$layout = $this->_buildLayoutPath($layout);

		return LayoutHelper::render($layout, ['vars' => $vars]);
	}

	/**
	 * Build Layout path
	 *
	 * @param   string  $layout  Layout name
	 *
	 * @return   string  Layout Path
	 * @throws Exception
	 * @since   1.0.0
	 *
	 */
	protected final function _buildLayoutPath(string $layout): string
	{
		return 'plugins.' . $this->_type . '.' . $this->_name . '.' . $layout . '.' . $layout;
	}

	/**
	 * Логирование работы плагина
	 *
	 * @param   string  $type     Тип записи
	 * @param   string  $message  Сообщение для записи в лог (например имя переменной)
	 * @param   array   $data     Переменная для записи в лог
	 *
	 * @return void
	 * @see   ECLLOG
	 * @since 1.0.20
	 */
	protected final function _addLog(string $type, string $message, mixed $data = null): void
	{
		ECLLogging::add(
			array(
				"source"           => $this->_name,
				"enabled"          => $this->enabled_log,
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

	/**
	 * Логирование работы плагина
	 *
	 * @param   array  $data
	 *
	 * @return void
	 * @since      1.0.0
	 * @deprecated 1.0.20 use _addLog
	 */
	protected final function _logging(array $data): void
	{
		ECLLogging::add(
			array(
				"source"           => $this->_name,
				"enabled"          => $this->enabled_log,
				"logger"           => "ECLabs\\Library\\ECLLogging\\Loggers\\ECLabDefaultLogger",
				"back_trace_level" => 3
			),
			array(
				"timestamp" => "",
				"type"      => ECLLOG::INFO,
				"caller"    => "",
				"message"   => "",
				"data"      => $data,
			)
		);
	}

	/**
	 * Получает Id плагина
	 *
	 * @return mixed|null
	 *
	 * @since 1.0.1
	 */
	protected final function _getId(): mixed
	{
		$folder = str_replace(DS . $this->_name, '', str_replace(JPATH_PLUGINS . DS, '', $this->_plugin_path));
		$db     = Factory::getContainer()->get(DatabaseInterface::class);;
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
			->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
			->where($db->quoteName('element') . ' = ' . $db->quote($this->_name));

		return $db->setQuery($query)->loadResult();
	}

	/**
	 * Запускает обработчик события по имени для Joomla 4
	 *
	 * @param   string  $fn_name  Имя функции обработчика
	 * @param   Event   $event    Событие
	 *
	 * @return bool
	 *
	 * @deprecated @since 2.0.0
	 *
	 * @since      1.0.10
	 */
	protected final function _runEventHandler(string $fn_name, Event $event): bool
	{
		$args = $event->getArguments();
		if (is_array($args))
		{
			if (isset($args['result']))
				unset($args['result']);

			$ret = call_user_func_array(array($this, $fn_name), $args);
			if (!$event->hasArgument('result'))
			{
				$event->addArgument('result', array());
			}
			$result = $event->getArgument('result', null);
			if (is_array($result))
			{
				$result[] = $ret;
				$event->setArgument('result', $result);

				return (bool) $ret;
			}

		}

		return true;
	}

	/**
	 * Устанавливает путь к директории плагина
	 *
	 * @return void
	 * @since 2.0.0.
	 */
	protected function _setPluginPath(): void
	{
		$this->_plugin_path = JPATH_PLUGINS . DIRECTORY_SEPARATOR . $this->_type . DIRECTORY_SEPARATOR . $this->_name;
	}

	/**
	 * @since 1.0.0
	 */
	private function _setJVersion(): void
	{
		$this->jVersion = ECLVersion::getJoomlaVersion();
	}

}