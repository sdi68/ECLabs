<?php
/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Helpers;

use Joomla\CMS\Uri\Uri;
use Joomla\Uri\UriHelper;

class ECLUriHelper extends UriHelper
{
	/**
	 * Удаляет GET параметр (массив параметров) из адреса
	 *
	 * @param   string        $url        Исходный url
	 * @param   string|array  $get_param  Наименование параметра или массив GET параметров
	 *
	 * @return string
	 * @since 2.0.1
	 */
	public static function removeGetParam(string $url, string|array $get_param): string
	{

		$uri = Uri::getInstance($url);
		// Получаем все GET‑параметры
		$queryParams = $uri->getQuery(true);
		if (is_array($queryParams))
		{
			if (is_array($get_param))
			{
				foreach ($get_param as $v)
				{
					if (isset($queryParams[$v]))
					{
						// Удаляем нужный параметр
						unset($queryParams[$v]);
					}
				}
			}
			else
			{
				if (isset($queryParams[$get_param]))
				{
					// Удаляем нужный параметр
					unset($queryParams[$get_param]);
				}
			}
			// Обновляем query-часть URL
			$uri->setQuery($queryParams);
		}

		// Возвращаем полный URL без параметра
		return $uri->toString();
	}
}