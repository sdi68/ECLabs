<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

/**
 * @package     ECLabs library
 *
 * Field to show list of plugins from the selected folder
 *
 * @since 1.0.0
 */
class JFormFieldPluginList extends JFormFieldList {
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
     * Method to attach a Form object to the field.
     *
     * @param SimpleXMLElement $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
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
    public function setup(SimpleXMLElement $element, $value, $group = null): bool
    {
        $return = parent::setup($element, $value, $group);

        if ($return)
        {
            $this->folder      = (string) $this->element['folder'];
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
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('extension_id'))
            ->select($db->quoteName('element'))
            ->select($db->quoteName('enabled'))
            ->from($db->qn('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($this->folder))
            ->order($db->quoteName('element'));
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