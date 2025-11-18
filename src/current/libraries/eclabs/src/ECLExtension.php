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
use JLog;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;


define('ECL_UPDATE_SERVER_URL', 'https://econsultlab.ru');

/**
 * Класс работы с расширениями
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLExtension
{
	/**
	 * URL сервера обновлений
	 * @since 1.0.0
	 */
	private const string _ECL_UPDATE_SERVER_URL = ECL_UPDATE_SERVER_URL;

	/**
	 * Элемент манифеста содержащий тип обновления расширений ECL
	 * @since 2.0.0
	 */
	public const string _ECL_EXTENSION_TYPE_TAG_NAME = 'dlid';

	/**
	 * Атрибут элемента манифеста содержащий тип обновления расширений ECL
	 * @since 2.0.0
	 */
	public const string _ECL_EXTENSION_TYPE_TAG_ATTR = 'ecltype';

	/**
	 * Значение определяющее бесплатный тип обновления расширений ECL
	 * @since 2.0.0
	 */
	public const string _ECL_EXTENSION_TYPE_FREE = 'free';

	/**
	 * Значение определяющее платный тип обновления расширений ECL
	 * @since 2.0.0
	 */
	public const string _ECL_EXTENSION_TYPE_PAID = 'paid';

	/**
	 * Получает информацию по обновлениям расширения для пользователя
	 *
	 * @param   string  $extension  Наименование расширения
	 * @param   string  $user_name  Имя пользователя
	 * @param   string  $password   Пароль пользователя
	 *
	 * @return false|mixed
	 *
	 * @throws Exception
	 * @see        getUpdateFromServer
	 * @deprecated since version 1.0.8
	 * @since      1.0.0
	 */
	public static function checkUpdate(string $extension, string $user_name, string $password): mixed
	{
		$params = array('user' => $user_name, 'password' => $password, 'element' => self::prepareElementName($extension));
		$params = ECLAuthorisation::encodeAuthorisationParams($params);
		$link   = self::_ECL_UPDATE_SERVER_URL . '/index.php?option=com_swjprojects&view=token&params=' . $params;
		$data   = ECLHttp::get($link);
		if (empty($data))
		{
			return false;
		}

		return $data;
	}

	/**
	 * Преобразует элемент к формату хранения в SWJProjects.
	 * Например: plg_xxx, com_xxx
	 *
	 * @param   string  $element  Значение элемента как в #__extensions
	 *
	 * @return string
	 *
	 * @since 2.0.0.
	 *
	 */
	public static function prepareElementName(string $element): string
	{
		$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('type'))
			->from($dbo->quoteName('#__extensions'))
			->where($dbo->quoteName('element') . ' = ' . $dbo->quote($element));
		$dbo->setQuery($query);
		$type   = $dbo->loadResult();
		$suffix = "";
		switch ($type)
		{
			case "component":
				$suffix = "com_";
				break;
			case "plugin":
				$suffix = "plg_";
				break;
			case "package":
				$suffix = "pkg_";
				break;
			case "library":
				$suffix = "lib_";
				break;
			case "module":
				$suffix = "mod_";
				break;
			case "language":
			case "template":
			case "file":
			default:
				break;
		}

		return str_starts_with($element, $suffix) ? $element : $suffix . $element;
	}

	/**
	 * Получает информацию по обновлениям расширения
	 *
	 * @param   string  $extension  Наименование расширения
	 * @param   array   $user_data  Данные пользователя расширения (имя и пароль или токен)
	 *
	 * @return false|mixed
	 *
	 * @throws Exception
	 * @since 1.0.8
	 */
	public static function getUpdateFromServer(string $extension, array $user_data): mixed
	{
		$params = array('user_data' => $user_data['ECL'], 'element' => self::prepareElementName($extension));
		$params = ECLAuthorisation::encodeAuthorisationParams($params);
		$link   = self::_ECL_UPDATE_SERVER_URL . '/index.php?option=com_swjprojects&view=token&params=' . $params;
		try
		{
			$data = ECLHttp::get($link);
		}
		catch (Exception $e)
		{
			JLog::add($e, JLog::ERROR, "ECLExtension::getUpdateFromServer");

			return false;
		}
		if (empty($data))
		{
			return false;
		}

		return $data;
	}

	/**
	 * Сохраняет авторизационные данные пользователя для сервера обновлений в поле custom_data расширения
	 * {"ECL":{"user":"admin","password":"o6_O4_1968","has_token":"","token":"","project_id":""}}
	 *
	 * @param   string  $extension  Имя расширения
	 * @param   array   $data       Авторизационные данные
	 *
	 * @since 1.0.0
	 */
	public static function setCustomData(string $extension, array $data): void
	{
		$out        = self::getCustomData($extension);
		$out['ECL'] = ECLTools::encodeParams($data, true);
		$json       = json_encode($out);
		$dbo        = Factory::getContainer()->get(DatabaseInterface::class);
		$query      = $dbo->getQuery(true);
		$query->update($dbo->quoteName('#__extensions'))
			->set($dbo->quoteName('custom_data') . ' = ' . $dbo->quote($json))
			->where($dbo->quoteName('name') . ' = ' . $dbo->quote($extension));
		$dbo->setQuery($query);
		$dbo->execute();
		self::_storeToken($extension, $data['token']);
	}

	/**
	 * Получает параметры пользователя из поля custom_data для данного расширения
	 *
	 * @param   string  $extension  Имя расширения
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function getCustomData(string $extension): array
	{
		$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('custom_data'))
			->from($dbo->quoteName('#__extensions'))
			->where($dbo->quoteName('name') . ' = ' . $dbo->quote($extension));
		$dbo->setQuery($query);
		$ret = $dbo->loadResult();

		$out = array();
		if ($ret)
		{
			$ret = json_decode($ret, true);
			if (isset($ret['ECL']))
				$out['ECL'] = ECLTools::decodeParams($ret['ECL']);
		}


		return $out;
	}

	/**
	 * Сохраняет токен пользователя для доступа на сервер обновлений для данного расширения
	 * в поле extra_query
	 *
	 * @param   string  $extension  Имя расширения
	 * @param   string  $token      Токен доступа
	 *
	 * @since 1.0.0
	 */
	private static function _storeToken(string $extension, string $token)
	{
		$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('update_site_id'))
			->from($dbo->quoteName('#__extensions', 'e'))
			->innerJoin($dbo->quoteName('#__update_sites_extensions', 'es') . ' ON (' . $dbo->quoteName('e.extension_id') . ' = ' . $dbo->quoteName('es.extension_id') . ')')
			->where($dbo->quoteName('e.name') . ' = ' . $dbo->quote($extension));
		$dbo->setQuery($query);
		$update_site_id = $dbo->loadResult();

		$query = $dbo->getQuery(true);
		$query->update('#__update_sites')
			->set($dbo->quoteName('extra_query') . '= ""')
			->where($dbo->quoteName('update_site_id') . ' = ' . $dbo->quote($update_site_id));

		$dbo->setQuery($query)->execute();

		$extra_query = $token ? 'download_key=' . $token : '';

		$query = $dbo->getQuery(true);
		$query->update('#__update_sites')
			->set($dbo->quoteName('extra_query') . '=' . $dbo->quote($extra_query))
			->where($dbo->quoteName('update_site_id') . ' = ' . $dbo->quote($update_site_id));

		$dbo->setQuery($query)->execute();

		Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController()->clean('_system');
	}

	/**
	 * Получает поле element по имени расширения
	 *
	 * @param   string  $extension  Имя расширения
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function getElement(string $extension): string
	{
		$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('element'))
			->from($dbo->quoteName('#__extensions'))
			->where($dbo->quoteName('name') . ' = ' . $dbo->quote($extension));
		$dbo->setQuery($query);

		return $dbo->loadResult();
	}

	/**
	 * Генерирует параметры для формирования URL сервера обновлений
	 *
	 * @param   int          $eid
	 * @param   string       $name
	 * @param   string       $type
	 * @param   string       $location
	 * @param   bool         $enabled
	 * @param   string|null  $extraQuery
	 *
	 * @return array|bool
	 *
	 * @since 1.0.0
	 */
	public static function generateXMLLocation(int $eid, string $name, string $type, string $location, bool $enabled, ?string $extraQuery = ''): bool|array
	{
		$user_info = self::getCustomData($name);
		if (isset($user_info['ECL']))
		{
			// Платное расширение ECL и есть данные по токену
			$hash       = self::_getXMLKey($user_info['ECL']['token'], $user_info['ECL']['project_id']);
			$xmlkey     = self::_buildExtraQueryString($hash);
			$extraQuery = self::_buildExtraQueryString($user_info['ECL']['token']);
			$location   .= ('&' . $xmlkey);

			return array(
				'extension_id' => $eid,
				'name'         => $name,
				'location'     => $location,
				'extra_query'  => $extraQuery
			);
		}

		return false;
	}

	/**
	 * Генерирует ключ на скачивание
	 *
	 * @param   string  $token
	 * @param   string  $salt
	 *
	 * @return string
	 *
	 * @see   SWJProjects SWJProjectsHelperKeys::checkKey
	 *
	 * @since 1.0.0
	 */
	private static function _getXMLKey(string $token, string $salt): string
	{
		return md5($token . '_' . $salt);
	}

	/**
	 * Генерирует параметр URL с ключем скачивания
	 *
	 * @param   string  $value
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private static function _buildExtraQueryString(string $value): string
	{
		return "download_key=" . $value;
	}

	/**
	 * Update an update site to download token info.
	 *
	 * @param   array  $updateSites  Update sites info array
	 *
	 * @return  void
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	public static function updateXMLLocation(array $updateSites): void
	{
		foreach ($updateSites as $updateSite)
		{
			$update_site_id = self::getUpdateSiteId($updateSite['extension_id']);
			if ($update_site_id)
			{
				// extra_query не используется
				self::updateECLLocationAndExtraQuery($update_site_id, $updateSite['location'], null);
			}
		}

	}

	/**
	 * Получает update_site_id из идентификатора расширения
	 *
	 * @param   int  $eid
	 *
	 * @return int
	 *
	 * @since 1.0.0
	 */
	public static function getUpdateSiteId(int $eid): int
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query->clear()
			->select($db->quoteName('update_site_id'))
			->from($db->quoteName('#__update_sites_extensions'));
		if (ECLVersion::getJoomlaVersion() <= 3)
		{
			$query->where($db->quoteName('extension_id') . '=' . $db->quote($eid));
		}
		else
		{
			$query->where(
				[
					$db->quoteName('extension_id') . ' = :extensionid',
				]
			)
				->bind(':extensionid', $eid, ParameterType::INTEGER);
		}
		$db->setQuery($query);

		return (int) $db->loadResult();
	}

	/**
	 * Обновляет значения location и extra_query
	 *
	 * @param   int          $update_site_id
	 * @param   string       $location
	 * @param   string|null  $extra_query
	 *
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public static function updateECLLocationAndExtraQuery(int $update_site_id, string $location, ?string $extra_query): void
	{
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__update_sites'))
			->set($db->quoteName('location') . ' = ' . $db->quote($location))
			->set($db->quoteName('extra_query') . ' = ' . $db->quote($extra_query));
		if (ECLVersion::getJoomlaVersion() <= 3)
		{
			$query->where($db->quoteName('update_site_id') . '=' . $db->quote($update_site_id));
		}
		else
		{
			$query->where(
				[
					$db->quoteName('update_site_id') . ' = :update_site_id',
				]
			)
				->bind(':update_site_id', $update_site_id, ParameterType::INTEGER);
		}
		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			Factory::getApplication()->enqueueMessage(Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()), 'error');
		}
	}

	/**
	 * Проверяет тип расширения ECL по update_site_id.
	 * Ищет элемент <dlid ecltype="paid|free"/> или
	 * для совместимости с ранними версиями элемент <ecltype>paid|free</ecltype>
	 *
	 * @param   int  $update_site_id
	 *
	 * @return string
	 * @throws Exception
	 * @since 2.0.0
	 */
	public static function checkECLTypeByUpdateSiteId(int $update_site_id): string
	{
		$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('extension_id'))
			->from($dbo->quoteName('#__update_sites_extensions'))
			->where($dbo->quoteName('update_site_id') . ' = ' . $dbo->quote($update_site_id));
		$dbo->setQuery($query);
		$extension_id = $dbo->loadResult();

		return self::checkECLType($extension_id);
	}

	/**
	 * Get type of ECL extension, like as paid or free.
	 * Тне type contains in manifest file in tag "ecltype"
	 * For example:
	 * <ecltype>paid</ecltype>
	 * or
	 * <ecltype>free</ecltype>
	 *
	 * @param   int  $extension_id  Id extension
	 *
	 * @return string
	 * @throws Exception
	 * @since 1.0.8
	 */
	public static function checkECLType(int $extension_id): string
	{
		$dbo   = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('element'))
			->select($dbo->quoteName('type'))
			->select($dbo->quoteName('folder'))
			->select($dbo->quoteName('client_id'))
			->from($dbo->quoteName('#__extensions'))
			->where($dbo->quoteName('extension_id') . ' = ' . $dbo->quote($extension_id));
		$dbo->setQuery($query);
		$info = $dbo->loadAssoc();
		if ($info)
		{
			switch ($info['type'])
			{
				case "component":
					$m_path = JPATH_ADMINISTRATOR . '/components/' . $info['element'] . '/' . str_replace('com_', '', $info['element']) . '.xml';
					break;
				case "plugin":
					$m_path = JPATH_PLUGINS . '/' . $info['folder'] . '/' . $info['element'] . '/' . $info['element'] . '.xml';
					break;
				case "package":
					$m_path = JPATH_MANIFESTS . '/packages/' . $info['element'] . '.xml';
					break;
				case "library":
					$m_path = JPATH_MANIFESTS . '/libraries/' . $info['element'] . '.xml';
					break;
				case "module":
					$m_path = ($info['client_id'] ? JPATH_SITE : JPATH_ADMINISTRATOR) . '/modules/' . $info['element'] . '/' . $info['element'] . '.xml';
					break;
				case "language":
					// TODO: где файл манифеста языка?
					return "";
				case "template":
					if ($info['client_id'])
					{
						$m_path = JPATH_THEMES . '/' . $info['element'] . '/templateDetails.xml';
					}
					else
					{
						$m_path = JPATH_ADMINISTRATOR . '/templates/' . $info['element'] . '/templateDetails.xml';
					}
					break;
				case "file":
					$m_path = JPATH_MANIFESTS . '/files/' . $info['element'] . '.xml';
					break;
				default:
					return "";
			}

			return self::checkECLTypeByManifest($m_path);
		}

		return "";
	}

	/**
	 * Проверяет тип расширения ECL по файлу манифеста.
	 * Ищет элемент <dlid ecltype="paid|free"/> или
	 * для совместимости с ранними версиями элемент <ecltype>paid|free</ecltype>
	 *
	 * @param   string  $path  Абсолютный путь до манифеста расширения
	 *
	 * @return string
	 *
	 * @since 2.0.0
	 */

	public static function checkECLTypeByManifest(string $path): string
	{
		if (file_exists($path))
		{
			$xml = simplexml_load_file($path);
			foreach ($xml->children() as $item)
			{
				if ($item->getName() === self::_ECL_EXTENSION_TYPE_TAG_NAME)
				{
					foreach ($item->attributes() as $attr)
					{
						if ($attr->getName() === self::_ECL_EXTENSION_TYPE_TAG_ATTR)
						{
							return (string) $attr;
						}
					}
				}
				// TODO Оставлено для совместимости с прежним механизмом
				else if ($item->getName() === self::_ECL_EXTENSION_TYPE_TAG_ATTR)
				{
					return (string) $item;
				}
			}
		}

		return "";
	}
}