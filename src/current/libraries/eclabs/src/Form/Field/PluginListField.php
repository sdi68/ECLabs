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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;



require_once JPATH_LIBRARIES . '/eclabs/src/autoload.php';

/**
 * @package     ECLabs library
 *
 * Field to show list of plugins from the selected folder
 *
 * @since 1.0.0
 */
class PluginListField extends ListField {
    /**
     * The form field type.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $type = 'PluginList';

    /**
     * The plugins folder
     *
     * @var string
     * @since  1.0.0
     */
    protected $folder = 'system';

	/**
	 * Анализируемые имена плагинов.
	 * Определяет какие плагины будут анализироваться (все, список имен или по маске на имя)
	 * Пусто - все плагины группы
     * Имена плагинов через запятую
     * Маска на имя плагина, например: %имя%,sditracking_%
     * @var string
     * @since 1.0.24
     */
    protected $plugins = "";

    /**
     * Method to attach a Form object to the field.
     *
     * @param \SimpleXMLElement $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed              $value    The form field value to validate.
     * @param   string             $group    The field name group control value. This acts as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     *
     * @return  boolean  True on success.
     *
     * @see     FormField::setup()
     * @since   1.0.0
     */
    public function setup(\SimpleXMLElement $element, $value, $group = null): bool
    {
        $return = parent::setup($element, $value, $group);

        if ($return)
        {
            $this->folder      = (string) $this->element['folder'];
	        /** @since 1.0.24 */
            $this->plugins = (string) $this->element['plugins'];
        }

        return $return;
    }


    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   1.0.0
     */
    public function getOptions(): array
    {
        $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
        $options   = array();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select($db->quoteName('extension_id'))
            ->select($db->quoteName('element'))
            ->select($db->quoteName('enabled'))
            ->from($db->qn('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($this->folder));
		/** @since 1.0.24 */
        if (!empty($this->plugins))
        {
            $names = explode(",", $this->plugins);
            foreach ($names as $name)
            {
                if (str_contains($name, '%'))
                {
                    // Передана маска на имя
                    $query->where($db->qn('element') . ' LIKE ' . $db->q($name), 'OR');
                }
                else
                {
                    //  Передано имя плагина
                    $query->where($db->qn('element') . ' = ' . $db->q($name), 'OR');
                }
            }
        }
        $query->order($db->quoteName('element'));
        $plugins = $db->setQuery($query)->loadAssocList();
        if($plugins){
            foreach ($plugins as $plugin) {

                $value = (string) $plugin['extension_id'];
                $text  = $plugin['element'];
                $disabled = !$plugin['enabled'];
                $disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');
                $disabled = $disabled || ($this->readonly && $value != $this->value);

                $checked = $value === $this->value;
                $checked = ($checked == 'true' || $checked == 'checked' || $checked == '1');

                $selected = $value === $this->value;
                $selected = ($selected == 'true' || $selected == 'selected' || $selected == '1');

                $tmp = array(
                    'value'    => $value,
                    'text'     => Text::alt($text, $fieldname),
                    'disable'  => $disabled,
                    'class'    => "",
                    'selected' => ($checked || $selected),
                    'checked'  => ($checked || $selected),
                );

                // Add the option object to the result set.
                $options[] = (object) $tmp;
            }
        }
        return $options;
    }

}