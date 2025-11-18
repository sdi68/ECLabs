<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use vmCustomPlugin;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

if (!class_exists('vmCustomPlugin'))
	require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/plugins/vmcustomplugin.php';

/**
 * vmCustom plugin abstract class
 * @package ECLabs\Library
 * @since   1.0.0
 */
abstract class ECLvmCustomPlugin extends vmCustomPlugin
{

	/**
	 * Id custom field
	 * @var int
	 * @since 1.0.0
	 */
	protected $_virtuemart_custom_id = 0;

	/**
	 * @param $subject
	 * @param $config
	 *
	 * @since 1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->_virtuemart_custom_id = $this->_getVirtuemartCustomIdByJPluginId($config['id']);
	}

	/**
	 * Return virtualmart_custom_id
	 *
	 * @param   int  $custom_jplugin_id
	 *
	 * @return mixed|null
	 *
	 * @since 1.0.0
	 */
	protected final function _getVirtuemartCustomIdByJPluginId(int $custom_jplugin_id)
	{
		$dbo = Factory::getContainer()->get(DatabaseInterface::class);;
		$query = $dbo->getQuery(true);
		$query->select($dbo->quoteName('virtuemart_custom_id'))
			->from($dbo->quoteName('#__virtuemart_customs'))
			->where($dbo->quoteName('custom_jplugin_id') . '=' . $dbo->quote($custom_jplugin_id));
		$dbo->setQuery($query);

		return $dbo->loadColumn();
	}

	/**
	 * Decode activeOpt parameter. Used as event at the com_customfield
	 *
	 * @param   string  $name                  Plugins name
	 * @param   int     $virtuemart_custom_id  Id custom field
	 * @param   array   $activeOpt
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	abstract public function onDecodeCustomfiltersActiveOpt(string $name, int $virtuemart_custom_id, array &$activeOpt): bool;

	/**
	 * Build where condition to filtered list products query. Used as event at the com_customfield
	 *
	 * @param   string  $name                  Plugins name
	 * @param   int     $virtuemart_custom_id  Id customfield
	 * @param   string  $cf_name
	 * @param   string  $sel_field
	 * @param   array   $cf_values
	 * @param   array   $custom_search         conditions array
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	abstract public function onGenerateCustomfiltersWhereCondition(string $name, int $virtuemart_custom_id, string $cf_name, string $sel_field, array $cf_values, array &$custom_search): bool;

	/**
	 * Get plugin data type. Used as event at the com_customfield
	 *
	 * @param   string  $name                  Plugins name
	 * @param   int     $virtuemart_custom_id  Id custom field
	 * @param   string  $data_type
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	abstract public function onGenerateCustomfilters(string $name, int $virtuemart_custom_id, string &$data_type): bool;

	/**
	 * Get plugin parameters. Used as event at the com_customfield
	 *
	 * @param   string  $name                  Plugins name
	 * @param   int     $virtuemart_custom_id  Id custom field
	 * @param   string  $product_customvalues_table
	 * @param   string  $customvalues_table
	 * @param   string  $filter_by_field
	 * @param   string  $customvalue_value_field
	 * @param   string  $filter_data_type
	 * @param   string  $sort_by
	 * @param   int     $custom_parent_id
	 * @param   string  $value_parent_id_field
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	abstract public function onFilteringCustomfilters(
		string $name,
		int    $virtuemart_custom_id,
		string &$product_customvalues_table,
		string &$customvalues_table,
		string &$filter_by_field,
		string &$customvalue_value_field,
		string &$filter_data_type,
		string &$sort_by,
		int    &$custom_parent_id,
		string &$value_parent_id_field
	): bool;

	/**
	 * Update custom plugin data (customfield_params) after save product.
	 * Fix escape unicode chars.
	 *
	 * @param $data
	 * @param $product_data
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	public final function plgVmAfterStoreProduct(&$data, &$product_data): bool
	{
		if (isset($data['customfield_params']) && is_array($data['customfield_params']))
		{
			foreach ($data['customfield_params'] as $k => $v)
			{
				$str = "";
				if (isset($data['field']) && is_array($data['field']))
				{
					if (isset($data['field'][$k]))
					{
						$fld_data = $data['field'][$k];
						if ($this->_itsMe($fld_data['custom_element'], $fld_data['virtuemart_custom_id']))
						{
							if (is_array($this->_varsToPushParam))
							{
								foreach ($this->_varsToPushParam as $param => $p_v)
								{
									if (isset($v[$param]))
									{
										$str .= ECLTools::buildParamString($param, $v[$param]);
									}
								}

								$dbo = Factory::getContainer()->get(DatabaseInterface::class);;
								$query = $dbo->getQuery(true);
								$query->update($dbo->quoteName('#__virtuemart_product_customfields'))
									->set($dbo->quoteName('customfield_params') . ' = ' . $dbo->quote($str))
									->where($dbo->quoteName('virtuemart_customfield_id') . ' = ' . $dbo->quote($fld_data['virtuemart_customfield_id']));
								$dbo->setQuery($query);
								try
								{
									$dbo->execute();
								}
								catch (\Exception $e)
								{
									vmError(Text::sprintf('ECLABS_LIBRARY_VMCUSTOMPLUGIN_ERROR_SAVING_DATA', $fld_data['custom_element'], $e->getMessage()));
								}
							}
						}
					}
				}

			}
		}

		return true;
	}

	/**
	 * Check what plugin is called
	 *
	 * @param   string    $name                  Plugins name
	 * @param   int|null  $virtuemart_custom_id  Id custom field
	 *
	 * @return bool
	 *
	 * @since 1.0.0
	 */
	protected function _itsMe(string $name, int $virtuemart_custom_id = null): bool
	{
		$ret = $name === $this->_name;
		//$ret &= (is_null($virtuemart_custom_id) ? $this->_virtuemart_custom_id : $virtuemart_custom_id) == $this->_virtuemart_custom_id;

		if (is_null($virtuemart_custom_id))
		{
			$ret &= true;
		}
		elseif (is_array($this->_virtuemart_custom_id))
		{
			$ret &= in_array($virtuemart_custom_id, $this->_virtuemart_custom_id);
		}
		else
		{
			$ret &= false;
		}

		return $ret;
	}
}