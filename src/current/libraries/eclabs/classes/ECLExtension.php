<?php
/**
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * testeconsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

define('ECL_UPDATE_SERVER_URL','@UPDATE_SERVER_URL@');
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
	private const _ECL_UPDATE_SERVER_URL = ECL_UPDATE_SERVER_URL;

	/**
	 * Получает информацию по обновлениям расширения для пользователя
	 *
	 * @param   string  $extension  Наименование расширения
	 * @param   string  $user_name  Имя пользователя
	 * @param   string  $password   Пароль пользователя
	 *
	 * @return false|mixed
	 *
	 * @throws \Exception
	 * @since 1.0.0
	 */
	public static function checkUpdate(string $extension, string $user_name, string $password)
	{
		$params = array('user' => $user_name, 'password' => $password, 'element' => $extension);
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
		$dbo   = Factory::getDbo();
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
				$out['ECL'] = $ret['ECL'];
		}

		return $out;
	}

	/**
	 * Сохраняет авторизационные данные пользователя для сервера обновлений в поле custom_data расширения
	 *
	 * @param   string  $extension  Имя расширения
	 * @param   array   $data       Авторизационные данные
	 *
	 * @since 1.0.0
	 */
	public static function setCustomData(string $extension, array $data): void
	{
		$out        = self::getCustomData($extension);
		$out['ECL'] = $data;
		$json       = json_encode($out);
		$dbo        = Factory::getDbo();
		$query      = $dbo->getQuery(true);
		$query->update($dbo->quoteName('#__extensions'))
			->set($dbo->quoteName('custom_data') . ' = ' . $dbo->quote($json))
			->where($dbo->quoteName('name') . ' = ' . $dbo->quote($extension));
		$dbo->setQuery($query);
		$dbo->execute();
		self::_storeToken($extension, $data['token']);
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
		$dbo   = Factory::getDbo();
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

		Factory::getCache()->clean('_system');
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
		$dbo   = Factory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('element'))
			->from($dbo->quoteName('#__extensions'))
			->where($dbo->quoteName('name') . ' = ' . $dbo->quote($extension));
		$dbo->setQuery($query);

		return $dbo->loadResult();
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
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->clear()
			->select($db->quoteName('update_site_id'))
			->from($db->quoteName('#__update_sites_extensions'));
        if(ECLVersion::getJoomlaVersion() <= 3 ) {
            $query->where($db->quoteName('extension_id').'='.$db->quote($eid));
        } else {
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
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__update_sites'))
			->set($db->quoteName('location') . ' = ' . $db->quote($location))
			->set($db->quoteName('extra_query') . ' = ' . $db->quote($extra_query));
        if(ECLVersion::getJoomlaVersion() <= 3 ) {
            $query->where($db->quoteName('update_site_id') . '='.$db->quote($update_site_id));
        } else {
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
	public static function generateXMLLocation(int $eid, string $name, string $type, string $location, bool $enabled, ?string $extraQuery = '')
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
}