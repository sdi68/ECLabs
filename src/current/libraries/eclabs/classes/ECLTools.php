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

use Exception;
use JConfig;
use JEventDispatcher;
use JLayoutHelper;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

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
	 * @since   1.0.0
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
        if(!isset($paths[$name])) {
                $config = new JConfig();
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
	public static function encodeParams(array $params, $unescape_unicode = false): string
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
	 * Вызывает события для различных версий Jooomla
	 *
	 * @param   string  $name  Имя события
	 * @param   array   $e     Аргументы события
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public static function triggerEvent(string $name, array $e = array()): array
	{

		switch (ECLVersion::getJoomlaVersion())
		{
			case "4":
				return Factory::getApplication()->triggerEvent($name, $e);
			case "3":
			default:
				$dispatcher = JEventDispatcher::getInstance();

				return $dispatcher->trigger($name, $e);
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
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			foreach ($plugins as $folder => $item)
			{
				$query->clear();
				$query->select('`extension_id`, `element`, `enabled`,`manifest_cache`,`folder`')
					->from($db->qn('#__extensions'))
					->where($db->qn('type') . ' = ' . $db->q('plugin'))
					->where($db->qn('folder') . ' = ' . $db->q($folder));
				if (!empty($item['plugins']))
				{
					$names = explode(",", $item['plugins']);
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
				$result = $db->setQuery($query)->loadAssocList();
				$path   = 'blocks.plugins_statuses.' . $layout . ECLVersion::getJoomlaVersionSuffix("_j");
				$return .= JLayoutHelper::render($path, array("plugins_info" => $result, "folder" => $folder, "title" => $item["title"]), JPATH_LIBRARIES . '/eclabs/layouts');
			}

		}

		return $return;
	}

}