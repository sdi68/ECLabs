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

defined('_JEXEC') or die;

use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Factory;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';


/**
 * About ECL field
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class JFormFieldECL_About extends JFormField
{
	/**
	 * Feld type name
	 * @var string
	 * @since 1.0.0
	 */
	public $type = 'About';
	/**
	 * Extension page url
	 * @var string
	 * @since 1.0.0
	 */
	protected $ext_page = '';
	/**
	 * Extension documentation url
	 * @var string
	 * @since 1.0.0
	 */
	protected $ext_doc = '';
	/**
	 * Path to extension manifest file
	 * @var string
	 * @since 1.0.0
	 */
	protected $this_xml_path = '';

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   Form  $form  The form to attach to the form field object.
	 *
	 * @since   1.7.0
	 */
	public function __construct($form = null)
	{
		parent::__construct($form = null);
		ECLLanguage::loadLibLanguage();
	}

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to get the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   3.2
	 */
	public function __get($name)
	{
		if ($name === 'ext_page' || $name === 'ext_doc' || $name === 'this_xml_path')
		{
			return $this->$name;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param   string  $name   The property name for which to set the value.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'ext_page':
			case 'ext_doc':
			case 'this_xml_path':
				$this->$name = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}


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
	 * @since   3.2
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->ext_page      = (string) $this->element['ext_page'];
			$this->ext_doc       = (string) $this->element['ext_doc'];
			$this->this_xml_path = (string) $this->element['this_xml_path'];
		}

		return $return;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @throws Exception
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		// Подключаем скрипты админки
		switch (ECLVersion::getJoomlaVersion())
		{
			case '4':
				/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
				$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
				$wr = $wa->getRegistry();
				$wr->addRegistryFile('/media/eclabs/joomla.assets.json');
				$wa->useStyle('eclabs.about');
			default:
				$doc = JFactory::getDocument();
				$doc->addStyleSheet('/media/eclabs/css/about.css');
		}

		if (empty($this->ext_image) || !file_exists($this->ext_image))
		{
			$this->ext_image = '/media/eclabs/images/logo.png';
		}

		$info = simplexml_load_file(JPATH_SITE . $this->this_xml_path);

		$html = "<div class = \"about-wrap\">";
		$html .= "<div class = \"about-img\">";
		$html .= ('<img src = "' . $this->ext_image . '"/>');
		$html .= "</div>";
		$html .= "<div class = \"about-intro\">";
		$html .= "<div class = \"about-title\">";
		$html .= (JText::_($info->name) . '.<span> ' . Text::_("JVERSION") . ' ' . $info->version . '</span>');
		$html .= "</div>";
		$html .= "<div class = \"about-links\">";
		if (!empty($this->ext_page))
			$html .= ('<a href ="' . $this->ext_page . '">' . Text::_('ECLABS_ABOUT_FIELD_PAGE') . '</a>');
		if (!empty($this->ext_doc))
			$html .= ('<a href ="' . $this->ext_doc . '">' . JText::_('ECLABS_ABOUT_FIELD_DOC') . '</a>');
		$html .= "</div>";
		$html .= "<div class = \"about-copyright\">";
		$html .= $info->copyright;
		$html .= "</div>";
		$html .= "</div>";
		$html .= "</div>";

		return $html;
	}

	/**
	 * Render label html
	 * @return string
	 *
	 * @since 1.0.0
	 */
	protected function getLabel()
	{
		return '';
	}

	/**
	 * Get layout data
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	protected function getLayoutData()
	{
		$options = array('options' => array('class' => 'sdi-about-controls'));

		return array_merge(parent::getLayoutData(), $options);
	}

	/**
	 * render field
	 *
	 * @param   array  $options
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function renderField($options = array())
	{
		$options = array_merge($options, array('class' => 'sdi-about-controls'));

		return parent::renderField($options);
	}

}
