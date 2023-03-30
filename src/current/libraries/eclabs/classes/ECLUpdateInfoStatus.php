<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.1
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

defined('_JEXEC') or die;

ECLLanguage::loadLibLanguage();

/**
 * Класс статусов получения информации об обновлении расширения от сервера обновлений
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLUpdateInfoStatus extends AbstractEnum
{

	/**
	 *  Статус: информация успешно получена
	 * @since 1.0.0
	 */
	const ECLUPDATEINFO_STATUS_SUCCESS = "S";

	/**
	 * Статус: ошибка, данные пользователя отсутствуют
	 * @since 1.0.0
	 */
	const ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING = "EU";

	/**
	 * Статус: расширение не найдено на сервере обновлений
	 * @since 1.0.0
	 */
	const ECLUPDATEINFO_STATUS_ERROR_MISSING_EXTENSION = "EE";

	/**
	 * Статус: ошибка авторизации пользователя на сервере обновлений
	 * @since 1.0.0
	 */
	const ECLUPDATEINFO_STATUS_ERROR_AUTHORIZATION = "EA";

	/**
	 * Статус: ошибка, на сервере обновлений оттсутствует версия расширения
	 * @since 1.0.0
	 */
	const ECLUPDATEINFO_STATUS_ERROR_MISSING_VERSION = "EV";

	/**
	 * Статус: ошибка некорректный токен
	 * @since 1.0.0
	 */
	const ECLUPDATEINFO_STATUS_ERROR_MISSING_TOKEN = "ET";


	/**
	 * Статусы, нуждающиеся в валидации
	 * @var bool[]
	 * @since 1.0.0
	 */
	protected static $validValues = array(
		'ECLUPDATEINFO_STATUS_SUCCESS'                 => true,
		'ECLUPDATEINFO_STATUS_ERROR_AUTHORIZATION'     => true,
		'ECLUPDATEINFO_STATUS_ERROR_MISSING_VERSION'   => true,
		'ECLUPDATEINFO_STATUS_ERROR_MISSING_EXTENSION' => true,
		'ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING'  => true,
		'ECLUPDATEINFO_STATUS_ERROR_MISSING_TOKEN'     => true
	);

}