<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

\defined('_JEXEC') or die;

use Exception;
use JConfig;
use JLayoutHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use SimpleXMLElement;


/**
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLTools
{

	/**
	 * Build parameter string with unescaped unicode chars to save to DB.
	 * Resolve, for example, save customfields_params with safe_json_encode
	 * in /administrator/components/com_virtuemart/helpers/vmjsapi.php
	 * for example:
	 * param_name = "{$param_value}"|
	 *
	 * @param   string  $param_name   Parameter name
	 * @param   mixed   $param_value  Parameter value
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function buildParamString(string $param_name, $param_value): string
	{
		$str = $param_name . '=';
		$str .= json_encode($param_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$str .= '|';

		return $str;
	}

	/**
	 * Сохранить лог в файл.
	 *
	 * @param   string  $name     Имя файла
	 *
	 * @param   array   $data     Данные для сохранения в лог
	 *
	 * @param   bool    $enabled  Разрешить логирование
	 *
	 * @depecated Use ECLLogging::add();
	 * @since     1.0.0
	 *
	 */
	public static function Storelog(string $name, array $data, bool $enabled = false): void
	{
		if (!$enabled)
			return;

		// Add timestamp to the entry
		$entry = Factory::getDate()->format('[Y-m-d H:i:s]') . ' - ' . json_encode($data) . "\n";

		// Compute the log file's path.
		static $paths = array();
		if (!isset($paths[$name]))
		{
			$config      = new JConfig();
			$path[$name] = $config->log_path . '/' . $name . '.php';
			if (!file_exists($path[$name]))
			{
				file_put_contents($path[$name], "<?php die('Forbidden.'); ?>\n\n");
			}
		}
		file_put_contents($path[$name], $entry, FILE_APPEND);
	}

	/**
	 * Кодирует для передачи параметры
	 *
	 * @param   array  $params  Параметры
	 * @param   bool   $unescape_unicode
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function encodeParams(array $params, bool $unescape_unicode = false): string
	{
		if ($unescape_unicode)
			return base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE));
		else
			return base64_encode(json_encode($params));
	}

	/**
	 * Декодирует после передачи параметры
	 *
	 * @param   string  $params  Кодированная строка параметров
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function decodeParams(string $params): array
	{
		return json_decode(base64_decode($params), true);
	}


	/**
	 * Вызывает события для различных версий Joomla
	 *
	 * @param   string  $name  Имя события
	 * @param   array   $e     Аргументы события
	 *
	 * @return array
	 *
	 * @throws Exception
	 *
	 * @deprecated use PluginsHelper::triggerPlugins
	 *
	 * @since      1.0.0
	 */
	public static function triggerEvent(string $name, array $e = array()): array
	{

		switch (ECLVersion::getJoomlaVersion())
		{
			case '5':
			case "4":
				return Factory::getApplication()->triggerEvent($name, $e);
			case '6':
			case "3":
			default:
				throw new Exception(Text::_("ECLABS_ERROR_TRIGGER_EVENTS_NOT_SUPPORTED"));
		}
	}

	/**
	 * Формирует блок диагностики подключенных плагинов компонента
	 *
	 * @param   array   $plugins        Массив проверяемых плагинов
	 *                                  array(
	 *                                  [folder]=>array(                string Наименование каталога плагинов
	 *                                  "plugins =>[plugins],   string пустое - использовать все плагины каталога,
	 *                                  либо имена плагинов через запятую
	 *                                  либо маска имен плагина, например, receipts_%
	 *                                  "title" => [title]      Заголовок блока плагинов каталога
	 *                                  ),
	 *                                  ...
	 *                                  )
	 * @param   string  $layout         Шаблон вывода блока
	 *
	 * @return string
	 * @since 1.0.21
	 */
	public static function renderComponentPluginsStatus(array $plugins, string $layout = "default"): string
	{
		$return = "";
		if (count($plugins))
		{
			foreach ($plugins as $folder => $item)
			{
				$result = self::_getComponentFolderPlugins($folder, $item);
				if ($result)
				{
					$path   = 'blocks.plugins_statuses.' . $layout . ECLVersion::getJoomlaVersionSuffix("_j");
					$return .= JLayoutHelper::render($path, array("plugins_info" => $result, "folder" => $folder, "title" => $item["title"]), JPATH_LIBRARIES . '/eclabs/layouts');
				}
			}
		}

		return $return;
	}


	/**
	 * Формирует блок диагностики подключенных плагинов в XML для вывода в формах настройки плагинами.
	 * Например, в событии onContentPrepareForm
	 *
	 * @param   array  $plugins         Массив проверяемых плагинов
	 *                                  array(
	 *                                  [folder]=>array(string Наименование каталога плагинов
	 *                                  "plugins =>[plugins],   string пустое - использовать все плагины каталога,
	 *                                  либо имена плагинов через запятую
	 *                                  либо маска имен плагина, например, receipts_%
	 *                                  "title" => [title]      Заголовок блока плагинов каталога
	 *                                  ),
	 *                                  ...
	 *                                  )
	 * @param   Form   $form            Форма настроек
	 *
	 * @return void
	 * @throws Exception
	 * @since 1.0.22
	 */
	public static function getXMLComponentPluginsStatus(array $plugins, Form &$form): void
	{
		ECLLanguage::loadLibLanguage();
		switch (ECLVersion::getJoomlaVersion())
		{
			case 4:
				$class_warning = 'alert alert-warning';
				$class_info    = 'alert alert-info';
				break;
			case 3:
			default:
				$class_warning = 'alert alert-warning';
				$class_info    = 'alert alert-info';
		}
		$element = '<field name="check_plugins" type="note" label = "ECLABS_CHECK_PLUGINS_LABEL" description="" class = "' . $class_warning . '" />';
		$xml     = new SimpleXMLElement($element);
		$form->setField($xml, null, true, 'basic');

		if (count($plugins))
		{
			foreach ($plugins as $folder => $item)
			{
				$result = self::_getComponentFolderPlugins($folder, $item);
				if ($result)
				{
					$element = '<field name="check_plugins_' . $folder . '" type="note" label = "' . ($item['title'] ?? $folder) . '" description="" class = "' . $class_info . '" />';
					$xml     = new SimpleXMLElement($element);
					$form->setField($xml, null, true, 'basic');
					foreach ($result as $plugin)
					{
						$description = "";
						$extension   = 'plg_' . $plugin['folder'] . '_' . $plugin['element'];
						ECLLanguage::loadExtraLanguageFiles($extension, JPATH_ADMINISTRATOR);
						if (!empty($plugin['manifest_cache']))
						{
							$manifest_cache = json_decode($plugin['manifest_cache'], true);
							if (array_key_exists('description', $manifest_cache))
							{
								$description = Text::_($manifest_cache['description']);
							}
						}
						$class   = (PluginHelper::isEnabled($folder, $plugin['element']) ? "alert alert-success" : "alert alert-error");
						$text    = $plugin['element'] . (empty($description) ? '' : ' (' . $description . ')') . ' - ' . (PluginHelper::isEnabled($folder, $plugin['element']) ? Text::_('ECLABS_PLUGIN_STATE_ON') : Text::_('ECLABS_PLUGIN_STATE_OFF'));
						$element = '<field name="check_plugins_' . $plugin['extension_id'] . '" type="note" class = "' . $class . '" label = "" description="' . $text . '" />';
						$xml     = new SimpleXMLElement($element);
						$form->setField($xml, null, true, 'basic');
					}
				}
			}
		}
	}

	/**
	 * Получает плагины компонента из указанной группы
	 *
	 * @param   string  $folder   Группа плагинов
	 * @param   array   $options  Настройки отбора плагинов
	 *
	 * @return array|mixed
	 * @since 1.0.22
	 */
	private static function _getComponentFolderPlugins(string $folder, array $options): mixed
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query->select('`extension_id`, `element`, `enabled`,`manifest_cache`,`folder`')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('plugin'))
			->where($db->qn('folder') . ' = ' . $db->q($folder));
		if (!empty($options['plugins']))
		{
			$names = explode(",", $options['plugins']);
			foreach ($names as $name)
			{
				if (str_contains($name, '%'))
				{
					// Передана маска на имя
					$query->where($db->qn('element') . ' LIKE ' . $db->q($name), 'OR');
				}
				else
				{
					//  Передано имя плагина
					$query->where($db->qn('element') . ' = ' . $db->q($name), 'OR');
				}
			}
		}

		return $db->setQuery($query)->loadAssocList();
	}
}