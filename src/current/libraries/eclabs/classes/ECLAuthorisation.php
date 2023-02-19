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

use Joomla\CMS\User\User;
use JUserHelper;

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
		$params['password'] = self::_encrypt($params['password']);

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
		$user_id = JUserHelper::getUserId($params['user']);
		if ($user_id)
		{
			$user = new User($user_id);
			$pswd = self::_decrypt($params['password']);
			if (JUserHelper::verifyPassword($pswd, $user->password))
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