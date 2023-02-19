<?php
/**
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use Joomla\CMS\Factory;
use Joomla\CMS\Factory as JFactory;


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
	// TODO Заменить на боевой URL
	private const _ECL_UPDATE_SEVER_URL = 'https://dev.econsultlab.ru';

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
		$link   = self::_ECL_UPDATE_SEVER_URL . '/index.php?option=com_swjprojects&view=token&params=' . $params;
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

		JFactory::getCache()->clean('_system');
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

}