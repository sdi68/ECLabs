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

use ECLabs\Library\ECLTools;
use ECLabs\Library\ECLVersion;
use Exception;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\LayoutHelper;
use SimpleXMLElement;


trait ECLPluginDiagnosticMasterTrait
{
	use ECLPluginDiagnosticTrait;

	public function onGetRenderCheckedPlugins(string $context, string &$html, string $layout = "default"): bool
	{
		if ($context === $this->_diagnosticContext)
		{
			$checkedPluginsInfo = $this->_buildCheckedPluginsList();
			$path               = 'blocks.plugins_statuses.' . $layout . ECLVersion::getJoomlaVersionSuffix("_j");
			foreach ($checkedPluginsInfo as $folder => $item)
			{
				if ($item)
				{
					$html .= LayoutHelper::render($path, array("plugins_info" => $item["plugins"], "folder" => $folder, "title" => $item["title"]), JPATH_ROOT . "/layouts/libraries/eclabs");
				}
			}
		}

		return true;
	}

	/**
	 * Формирует список плагинов для отслеживания
	 * Формат массива:
	 *  [
	 *      {группа плагинов} =[
	 *          "title" =>{заголовок для группы плагинов},
	 *          "plugins" =>[
	 *              0 =>[
	 *                  'element' =>{наименование элемента плагина из манифеста},
	 *                  "description" => {описание плагина из манифеста},
	 *                  "extension" => {'plg_' . {группа плагинов} . '_' . {'element'}},
	 *                  "extension_id" => {идентификатор плагина},
	 *                  "enabled" => {флаг опубликован или нет}
	 *              ],
	 *              ...
	 *          ],
	 *          ...
	 *      ]
	 * ]
	 * @throws Exception
	 * @since 2.0.1
	 */
	abstract protected function _buildCheckedPluginsList(): array;

	/**
	 * Формирует XML поля в форме для вывода диагностической информации по отслеживаемым плагинам
	 *
	 * @param   Form  $form  Форма, куда добавляется диагностическая информация
	 *
	 * @throws Exception
	 * @since 2.0.1
	 */

	private function _getCheckedPluginsStatusAsXML(Form &$form): void
	{
		$checkedPluginsInfo = $this->_buildCheckedPluginsList();
		$element            = '<field name="check_plugins" type="note" label = "ECLABS_CHECK_PLUGINS_LABEL" description=""/>';
		$xml                = new SimpleXMLElement($element);
		$form->setField($xml, null, true, 'basic');
		$value   = ECLTools::encodeParams($checkedPluginsInfo);
		$element = '<field name="diagnostics_info" type="DiagnosticPlugins" default="' . $value . '"/>';
		$xml     = new SimpleXMLElement($element);
		$form->setField($xml, null, true, 'basic');
	}
}