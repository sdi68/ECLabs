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
		switch (true)
		{
			case $version->isCompatible('4.0'):
				return '4';
			case $version->isCompatible('3.0'):
				return '3';
			default:
				return '';
		}
	}

	/**
	 * Return suffix with major Joomla version
	 *
	 * @param   string  $suffix  Suffix
	 *
	 * @return string
	 *
	 * @since version
	 */
	public static function getJoomlaVersionSuffix(string $suffix = ""): string
	{
		return $suffix . self::getJoomlaVersion();
	}
}