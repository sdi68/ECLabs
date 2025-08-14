<?php
/**
 * @package        Econsult Labs Library
 * @version          __DEPLOYMENT_VERSION__
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
namespace ECLabs\Library\Form\Field;

\defined('_JEXEC') or die;

use ECLabs\Library\ECLVersion;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;



class TaskUrlField extends FormField
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected  $type = "TaskUrl";
	/**
	 * Name of the layout being used to render the field.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected $layout = 'default';
	/**
	 * Associated Token field name.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected $token_field = "token";
	/**
	 * Base part of URL task.
	 *
	 * @var  string
	 *
	 * @since  1.0.13
	 */
	protected $task_url = "";
	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag.
	 * @param   mixed             $value    The form field value to validate.
	 * @param   string            $group    The field name group control value.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  1.0.13
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null): bool
	{
		if ($return = parent::setup($element, $value, $group))
		{
			$this->token_field     = (!empty($this->element['token_field'])) ? $this->element['token_field'] : "";
			$this->task_url     = (!empty($this->element['task_url'])) ? $this->element['task_url'] : "";
			Text::script("ECL_TASKURL_ERROR_NEED_TOKEN");
			$suff = ECLVersion::getJoomlaVersionSuffix('_j');
			if(!str_contains($this->layout, $suff))
				$this->layout .= $suff;
			$this->layout        = 'libraries.eclabs.fields.taskurl.' . $this->layout;
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
		$data['token_field']     = $this->token_field;
		$data['task_url']     = $this->task_url;
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
		//return array_merge([JPATH_LIBRARIES . '/eclabs/layouts/taskurl/'], parent::getLayoutPaths());
		return parent::getLayoutPaths();
	}

}