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

define('ECLABS_ROOT_PATH', dirname(__FILE__));

/**
 * library class loader
 *
 * @param   string  $className  class name
 *
 *
 * @since 1.0.0
 */
function ECLabsLoadClass(string $className)
{
	$name_space = 'ECLabs\Library';
	$cnt        = strlen($name_space);
	if (strncmp($name_space, $className, $cnt) === 0)
	{
		$path   = ECLABS_ROOT_PATH;
		$length = $cnt;
	}
	else
	{
		return;
	}
	$path .= str_replace('\\', '/', substr($className, $length)) . '.php';

	if (file_exists($path))
	{
		require $path;
	}
}

spl_autoload_register('ECLabsLoadClass');
