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

use ECLabs\Library\ECLLanguage;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Контроль наличия обязательных плагинов.
 * Применяется, например, в DisplayController.
 * @since 2.0.1
 */
trait ECLCheckRequiredPluginsTrait
{
	/**
	 * @var array Массив контролируемых плагинов
	 * Формат элемента: ["folder"=>{тип плагина},"name"=>{Имя плагина}]
	 * @since 2.0.1
	 */
	protected array $_requiredPlugins = [];

	/**
	 * Проверяет наличие и запуск обязательных плагинов.
	 * Если найдены не установленные или не запущенные плагины выдается сообщение об ошибке.
	 * @throws Exception
	 * @since 2.0.1
	 */
	public function checkRequiredPlugins(): void
	{
		ECLLanguage::loadLibLanguage();
		$msg = "";
		foreach ($this->_requiredPlugins as $plugin)
		{
			if (!PluginHelper::isEnabled($plugin["folder"], $plugin["name"]))
			{
				$msg .= ">> plg_" . $plugin["folder"] . "_" . $plugin["name"] . "</br>";
			}
		}
		if (!empty($msg))
		{
			Factory::getApplication()->enqueueMessage(Text::sprintf("ECLABS_ERROR_REQUIRED_PLUGINS", $msg), 'error');
		}
	}
}