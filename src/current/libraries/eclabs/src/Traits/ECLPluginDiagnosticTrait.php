<?php
/**
 * @package             Econsult Labs Library
 * @version             2.0.1
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Traits;

trait ECLPluginDiagnosticTrait
{
	/**
	 * Контекст вызова диагностической информации
	 * @var string
	 * @since 2.0.1
	 */
	protected string $_diagnosticContext = "";

	/**
	 * Заголовок вывода диагностической информации о плагине
	 * @var string
	 * @since 2.0.1
	 */
	protected string $_diagnosticTitle = "";

	/**
	 * Построение элемента диагностической информации о плагине
	 * @return array[]
	 * @since 2.0.1
	 */
	protected function _buildRequiredItem(): array
	{
		return array(
			$this->_type => array(
				"plugin" => $this->_name,
				"title"  => $this->_diagnosticTitle
			)
		);
	}
}