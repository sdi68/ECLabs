<?php

/**
 * @package        Econsult Labs Library
 * @version          1.0.0
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use Joomla\CMS\Factory;

/**
 * Language class
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLLanguage
{
	/**
	 * Load library language for other extensions
	 *
	 * @since 1.0.0
	 */
	public static function loadLibLanguage()
	{
		Factory::getLanguage()->load('eclabs', JPATH_ROOT);
	}

	/**
	 * Load any extension language
	 *
	 * @param   string       $extension  Extension name
	 * @param   string       $basePath   Base path to language files
	 * @param   string|null  $lang       Language
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public static function loadExtraLanguageFiles(string $extension, string $basePath = JPATH_BASE, $lang = null): bool
	{
		$language = Factory::getLanguage();

		return $language->load($extension, $basePath, $lang);
	}
}