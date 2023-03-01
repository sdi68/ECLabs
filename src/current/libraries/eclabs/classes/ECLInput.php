<?php
/**
 * @package             Econsult Labs Library
 * @version             1.0.0
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2023 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use Joomla\Input\Input;

/**
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLInput extends Input
{

	/**
	 * Конструктор класса
	 *
	 * @param   bool   $use_php_input  Использовать как источник данных php://input
	 * @param   array  $options        Опции
	 *
	 * @since 1.0.0
	 */
	public function __construct(bool $use_php_input = false, array $options = array())
	{
		$source = null;
		if ($use_php_input)
		{
			$json   = file_get_contents('php://input');
			$source = json_decode($json, true);
		}
		parent::__construct($source, $options);
	}
}