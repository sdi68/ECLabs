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

defined('_JEXEC') or die;

/**
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLTools
{

	/**
	 * Build parameter string with unescaped unicode chars to save to DB.
	 * Resolve, for example, save customfields_params with safe_json_encode
	 * in /administrator/components/com_virtuemart/helpers/vmjsapi.php
	 * for example:
	 * param_name = "{$param_value}"|
	 *
	 * @param   string  $param_name   Parameter name
	 * @param   mixed   $param_value  Parameter value
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function buildParamString(string $param_name, $param_value): string
	{
		$str = $param_name . '=';
		$str .= json_encode($param_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$str .= '|';

		return $str;
	}

}