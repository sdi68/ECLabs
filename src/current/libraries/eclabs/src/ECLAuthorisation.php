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

use Joomla\CMS\User\User;
use Joomla\CMS\User\UserHelper;

/**
 * Класс авторизации на сервере обновлений
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLAuthorisation
{
	/**
	 * Кодирует для передачи параметры авторизации пользователя
	 *
	 * @param   array  $params  Параметры пользователя
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function encodeAuthorisationParams(array $params): string
	{
		// since version 1.0.8
		if (isset($params['password']))
		{
			$params['password'] = self::_encrypt($params['password']);
		}
		elseif (isset($params['user_data']['password']))
		{
			$params['user_data']['password'] = self::_encrypt($params['user_data']['password']);
		}

		return base64_encode(json_encode($params, JSON_UNESCAPED_UNICODE));
	}

	/**
	 * Декодирует после передачи параметры авторизации пользователя
	 *
	 * @param   string  $params  Кодированная строка параметров
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public static function decodeAuthorisationParams(string $params): array
	{
		return json_decode(base64_decode($params), true);
	}

	/**
	 * Проверяет возможность авторизации пользователя
	 *
	 * @param   array  $params  Параметры авторизации пользователя
	 *
	 * @return int Идентификатор пользователя на сервере обновлений
	 *
	 * @since 1.0.0
	 */
	public static function checkAuthorise(array $params): int
	{
		$user_id = UserHelper::getUserId($params['user']);
		if ($user_id)
		{
			$user = new User($user_id);
			$pswd = self::_decrypt($params['password']);
			if (UserHelper::verifyPassword($pswd, $user->password))
			{
				return $user_id;
			}
		}

		return 0;
	}

	/**
	 * Кодировщик строк
	 *
	 * @param   string  $str  Строка для кодирования
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private static function _encrypt(string $str): string
	{
		return base64_encode($str);
	}

	/**
	 * Декодировщик строк
	 *
	 * @param   string  $str  Кодированная строка
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private static function _decrypt(string $str): string
	{
		return base64_decode($str);
	}

}