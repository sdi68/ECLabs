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

use Joomla\CMS\Version;

/**
 * Library version class
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLVersion
{
	/**
	 * Return Joomla major version number
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function getJoomlaVersion(): string
	{
		$version = new Version();

		return match (true)
		{
			$version->isCompatible('6.0') => '6',
			$version->isCompatible('5.0') => '5',
			$version->isCompatible('4.0') => '4',
			$version->isCompatible('3.0') => '3',
			default => '',
		};
	}

	/**
	 * Return suffix with major Joomla version
	 *
	 * @param   string  $suffix  Suffix
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function getJoomlaVersionSuffix(string $suffix = ""): string
	{
		return $suffix . self::getJoomlaVersion();
	}

	/**
	 * Getting class name, assigned with current Joomla version
	 * @public
	 * @returns string
	 * @since 1.0.0
	 */
	public static function getJVersionClass(): string
	{
		return self::getJoomlaVersionSuffix('version-');
	}
}