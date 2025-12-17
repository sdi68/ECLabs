<?php

/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

\defined('_JEXEC') or die;

use Exception;
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
	 * @throws Exception
	 * @since 1.0.0
	 */
	public static function loadLibLanguage(): void
	{
		Factory::getApplication()->getLanguage()->load('lib_eclabs', JPATH_ROOT);
	}

	/**
	 * Load any extension language
	 *
	 * @param   string       $extension  Extension name (what is the name of language file, for example, plg_shop-connectors_virtuemart)
	 * @param   string       $basePath   Base path to language files
	 * @param   string|null  $lang       Language
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public static function loadExtraLanguageFiles(string $extension, string $basePath = JPATH_BASE, string $lang = null): bool
	{
		$language = Factory::getApplication()->getLanguage();

		return $language->load($extension, $basePath, $lang);
	}
}