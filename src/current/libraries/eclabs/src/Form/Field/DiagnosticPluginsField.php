<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Form\Field;

use ECLabs\Library\ECLTools;
use Joomla\CMS\Form\Field\TextField;

/**
 * Поле для вывода диагностической информации о плагинах приложения
 * @since __DEPLOYMENT_VERSION__
 */
class DiagnosticPluginsField extends TextField
{
	/**
	 * Field type name
	 * @var string
	 * @since __DEPLOYMENT_VERSION__
	 */
	public $type = 'DiagnosticPlugins';

	/**
	 * Layout по-умолчанию
	 * @var string
	 * @since __DEPLOYMENT_VERSION__
	 */
	protected $layout = 'default';


	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     FormField::setup()
	 * @since   __DEPLOYMENT_VERSION__
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null): bool
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->layout = 'libraries.eclabs.fields.diagnosticplugins.' . (!empty($this->element['layout']) ? (string) $this->element['layout'] : $this->layout);

		}

		return $return;
	}

	/**
	 * Формирует данные для вывода поля
	 * @return array
	 * @since   __DEPLOYMENT_VERSION__
	 */
	protected function getLayoutData(): array
	{
		$data       = parent::getLayoutData();
		$extra_data = array(
			"plugins" => ECLTools::decodeParams($data["value"])
		);

		return array_merge($data, $extra_data);
	}

	/**
	 * Удаляет вывод названия поля
	 * @return string
	 * @since   __DEPLOYMENT_VERSION__
	 */
	protected function getLabel(): string
	{
		return "";
	}
}