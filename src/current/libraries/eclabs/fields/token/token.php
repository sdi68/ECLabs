<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.19
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2024 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormField;
defined('_JEXEC') or die;

class JFormFieldToken extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected  $type = "token";
	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected $layout = 'default';
	/**
	 * Key length.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected $length = null;
	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  1.0.13
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null): bool
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->length     = (!empty((int) $this->element['length'])) ? $this->element['length'] : 10;
		}

		return $return;
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array Layout data array.
	 *
	 * @since  1.0.13
	 */
	protected function getLayoutData(): array
	{
		$data               = parent::getLayoutData();
		$data['length']     = (int) $this->length;
		return $data;
	}

	/**
	 * Set layout path
	 * @return array
	 *
	 * @since 1.0.13
	 */
	public function getLayoutPaths(): array
	{
		$suff = ECLVersion::getJoomlaVersionSuffix('_j');
		if(!str_contains($this->layout, $suff))
			$this->layout .= $suff;
		return array_merge([JPATH_LIBRARIES . '/eclabs/layouts/token/'], parent::getLayoutPaths());
	}

}