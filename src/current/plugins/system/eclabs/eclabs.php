<?php
/**
 * @package         Econsult Labs Library
 * @subpackage   Econsult Labs system plugin
 * @version           1.0.0
 * @author            ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use ECLabs\Library\ECLExtension;
use ECLabs\Library\ECLInput;
use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLPlugin;
use ECLabs\Library\ECLUpdateInfoStatus;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Table\BeforeStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\Component\Installer\Administrator\Model\InstallModel;
use Joomla\Database\ParameterType;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

$lang = Factory::getLanguage();
$lang->load('plg_system_eclabs', JPATH_ADMINISTRATOR);

/**
 * @package     Econsult Labs system plugin
 *
 * @since       1.0.0
 */
class PlgSystemECLabs extends ECLPlugin
{
	/**
	 * @var Installer
	 * @since 1.0.0
	 */
	private $_installer;
	/**
	 * Идентификатор устанавливаемого/обновляемого расширения
	 * @var int
	 * @since 1.0.0
	 */
	private int $_eid;

	/**
	 * Массив информации по отложенным изменениям расширений ECL
	 * @var array
	 * @since 1.0.0
	 */
	private $_updateSites = array();

	/**
	 * @param          $subject
	 * @param   array  $config
	 *
	 * @since       1.0.0
	 */
	public function __construct(&$subject, array $config = array())
	{
		parent::__construct($subject, $config);
		$this->enabled_log  = $this->params->get('logging', false);
		$this->_plugin_path = __DIR__;
	}


	/**
	 * Обработка события.
	 * Производится обновление таблицы #__update_sites информацией о платных расширениях ECL
	 * Информация формируется в событиях onExtensionAfterInstall и onExtensionAfterUpdate
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function onBeforeRender()
	{
		$app = Factory::getApplication();
		$doc = $app->getDocument();

		// Если рендер не html, то выходим
		if (!($doc instanceof HtmlDocument))
			return;

		// Если не admin - то выходим
		if (!$app->isClient('administrator'))
			return;

		$this->_updateSites = Factory::getApplication()->getUserState('eclabs.updateSites', false);
		$this->_logging(array('eclabs.updateSites' => $this->_updateSites));
		if (is_array($this->_updateSites))
		{
			//var_dump($this->_updateSites);
			ECLExtension::updateXMLLocation($this->_updateSites);
			Factory::getApplication()->setUserState('eclabs.updateSites', null);
		}
	}

	/**
	 * Формируем информацию для платных расширений ECL после установки
	 *
	 * @param   Installer  $installer
	 * @param   int        $eid
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function onExtensionAfterInstall(Installer $installer, int $eid)
	{
		$this->_logging(array('eid' => $eid, 'installer' => $installer));
		if ($eid)
		{
			$this->_installer = $installer;
			$this->_eid       = (int) $eid;

			// Handle any update sites
			$this->_processUpdateSites();
		}
	}

	/**
	 * Формируем информацию для платных расширений ECL после обновления или перестроения серверов обновлений
	 *
	 * @param   Installer  $installer
	 * @param   int        $eid
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function onExtensionAfterUpdate(Installer $installer, int $eid)
	{
		$this->_logging(array('eid' => $eid, 'installer' => $installer));
		if ($eid)
		{
			$this->_installer = $installer;
			$this->_eid       = (int) $eid;

			// Handle any update sites
			$this->_processUpdateSites();
		}
	}

	/**
	 * Обновляет информацию о сервере обновлений для платных расширений ECL
	 *
	 * @param   string       $location
	 * @param   string|null  $extra_query
	 *
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	private function _updateLocationAndExtraQuery(string $location, ?string $extra_query)
	{
		$update_site_id = ECLExtension::getUpdateSiteId($this->_eid);
		ECLExtension::updateECLLocationAndExtraQuery($update_site_id, $location, $extra_query);
	}

	/**
	 * Processes the list of update sites for an extension.
	 *
	 * @return  void
	 *
	 * @throws Exception
	 * @since   1.6
	 */
	private function _processUpdateSites(): void
	{
		$manifest      = $this->_installer->getManifest();
		$updateservers = $manifest->updateservers;

		if ($updateservers)
		{
			$children = $updateservers->children();
		}
		else
		{
			$children = [];
		}

		if (count($children))
		{
			foreach ($children as $child)
			{
				$attrs = $child->attributes();
				$this->_logging(array($this->_eid, (string) $attrs['name'], (string) $attrs['type'], trim($child), true, $this->_installer->extraQuery));
				$tmp = ECLExtension::generateXMLLocation($this->_eid, (string) $attrs['name'], (string) $attrs['type'], trim($child), true, $this->_installer->extraQuery);
				if ($tmp !== false)
				{
					// Вернем значение location из манифеста
					// extra_query не используется
					$this->_updateLocationAndExtraQuery(trim($child), null);
					$this->_updateSites[] = $tmp;
					$this->_logging(array('_updateSites' => $this->_updateSites));
					Factory::getApplication()->setUserState('eclabs.updateSites', $this->_updateSites);
				}
			}
		}
		else
		{
			$data = trim((string) $updateservers);

			if ($data !== '')
			{
				// We have a single entry in the update server line, let us presume this is an extension line
				$this->_logging(array(Text::_('PLG_EXTENSION_JOOMLA_UNKNOWN_SITE')));
			}
		}
	}


	/**
	 * Получение информации по расширению от сервера обновлений
	 *
	 * @param   string  $context       Контекст вызова (не используется)
	 * @param   string  $element_name  Имя расширения
	 * @param   array   $update_info   Массив для хранения информации по расширению
	 * @param   array   $user_data     Массив информации о пользователе сервера обновлений
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	private function getECLUpdateInfo(string $context, string $element_name, array &$update_info, array &$user_data): void
	{
		// TODO проверка контекста на данный момент не нужна
		if (empty($user_data['ECL']['user']) || empty($user_data['ECL']['password']))
		{
			// Нет данных пользователя или токена
			$user_data = ECLExtension::getCustomData($element_name);
			if (empty($user_data['ECL']['user']) || empty($user_data['ECL']['password']))
			{
				$update_info['error'] = array(
					'code'    => ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING,
					'message' => ECLUpdateInfoStatus::getEnumNameText(ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING)
				);

				return;
			}
		}
		$element                        = ECLExtension::getElement($element_name);
		$update_info                    = ECLExtension::checkUpdate($element, $user_data['ECL']['user'], $user_data['ECL']['password']);
		$user_data['ECL']['token']      = $update_info['token'] ?? '';
		$user_data['ECL']['project_id'] = $update_info['project_id'] ?? '';
		ECLExtension::setCustomData($element_name, $user_data['ECL']);
	}

	/**
	 * Формирует блок версии для xml поля about расширения для текущего пользователя сервера обновлений.
	 *
	 * @param   string  $context
	 * @param   array   $extension_info
	 * @param   string  $extension_name
	 * @param   bool    $is_free
	 * @param   array   $user_data
	 * @param   array   $update_info
	 * @param   string  $html
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function onRenderVersionBlock(string $context, array $extension_info, string $extension_name, bool $is_free, array $user_data, array &$update_info, string &$html): bool
	{
		// TODO проверка контекста
		if (!($context == "about" || $context == 'renderVersionBlock'))
			return false;

		ECLLanguage::loadLibLanguage();
		$version            = array();
		$version['current'] = (string) $extension_info['version'];
		$version['new']     = (string) $extension_info['version'];
		$version['error']   = "";
		$vars               = new stdClass();
		$vars->is_free      = $is_free;
		$vars->debug_mode   = $this->enabled_log;
		$this->getECLUpdateInfo('checkUpdate', $extension_name, $update_info, $user_data);
		$this->_logging(array('update_info', $update_info));

		switch (true)
		{
			case $is_free:
				// TODO Как получить с сервера обновлений SWJProjects информацию о бесплатном расширении
				$this->_logging(array('is free extension', $extension_name));
				$version['new'] = "";
				$vars->class    = $this->jVersion <= 3 ? "label label-success" : "alert-success";
				$vars->text     = "FREE";
				break;
			case !$update_info:
				// Данные не получены от сервера
				$version['error']                = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
				$vars->class                     = $this->jVersion <= 3 ? "label label-important" : "alert-danger";
				$vars->text                      = $version['error'];
				$update_info['error']['message'] = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
				break;
			case !empty($update_info['error']) && $update_info['error'] !== ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_SUCCESS :
				// Получена ошибка от сервера обновлений
				$version['error'] = $update_info['error']['message'];
				$version['new']   = "";
				$vars->class      = $this->jVersion <= 3 ? "label label-important" : "alert-danger";;
				$vars->text = $version['error'];
				break;
			case  !empty($update_info['token']):
				// Получен токен
				$version['new'] = $update_info['last_version'] ?? $version['current'];
				if ($version['new'] == $version['current'])
				{
					$version['new'] = "";
					$vars->class    = $this->jVersion <= 3 ? "label label-success" : "alert-success";
					$vars->text     = Text::_('ECLABS_ABOUT_FIELD_USED_LAST_VERSION');
				}
				else
				{
					$vars->class = $this->jVersion <= 3 ? "label label-warning" : "alert-warning";
					$vars->text  = Text::_('ECLABS_ABOUT_FIELD_NEW_VERSION');
				}
				break;
			default:
				$version['new'] = "";
				break;
		}

		$vars->version        = $version;
		$vars->container_id   = "version-" . $extension_info['name'];
		$vars->user_data      = $user_data;
		$vars->element_name   = $extension_name;
		$vars->extension_info = json_encode(array(
			'name'    => (string) $extension_info['name'],
			'version' => (string) $extension_info['version']
		), JSON_UNESCAPED_UNICODE);
		$html                 .= $this->_buildLayout($vars, 'authorize');

		return true;
	}


	/**
	 * Обработка обращений по AJAX
	 * @return void
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function onAjaxEclabs()
	{
		$input  = new ECLInput(true);
		$action = $input->get('action', '');
		$this->_logging(array('action', $action));
		$out = array('ok' => '', 'response' => '');

		switch ($action)
		{
			case "renderVersionBlock":
				$extension_info = $input->get('extension_info', '');
				$element_name   = $input->get('element_name', '');
				$user_data      = $input->get('user_data', array('ECL' => array('user' => '', 'password' => '')));
				$is_free        = $input->get('is_free', 0);
				$this->_logging(array('element_name', $element_name));
				$html        = "";
				$update_info = array();
				$this->onRenderVersionBlock('renderVersionBlock', $extension_info, $element_name, $is_free, $user_data, $update_info, $html);
				$out['response'] = array('extension' => $element_name, 'update_info' => $update_info, 'html' => $html);
				$out['ok']       = true;
				break;
			default:
				$out['ok'] = false;
				break;
		}
		echo json_encode($out, JSON_UNESCAPED_UNICODE);
		jexit();
	}

	/**
	 * Инициализация скриптов и стилей
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	protected final function _addMedia(): void
	{
		$js = "var ecl_jversion =" . ECLVersion::getJoomlaVersion() . ";";
		$js .= "var ecl_enable_log=" . $this->enabled_log . ";";

		ECLLanguage::loadLibLanguage();
		Text::script('JCLOSE');
		Text::script('JAPPLY');
		Text::script('JVERSION');
		switch (ECLVersion::getJoomlaVersion())
		{
			case '4':
				/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
				$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
				$wr = $wa->getRegistry();
				$wr->addRegistryFile('/media/plg_system_eclabs/joomla.assets.json');
				$wr->addRegistryFile('/media/eclabs/joomla.assets.json');

				$wa->useStyle('eclabs.request');
				$wa->useScript('eclabs.request');

				$wa->useStyle('eclabs.modal');
				$wa->useScript('eclabs.modal');

				$wa->useScript('plg_system_eclabs.version');

				$wa->useScript('bootstrap.modal');
				$wa->addInlineScript($js);
				break;
			default:
				$doc = JFactory::getDocument();
				$doc->addStyleSheet('/media/eclabs/css/about.css');
				$doc->addStyleSheet('/media/eclabs/css/ecl_modal.css');
				$doc->addStyleSheet('/media/eclabs/css/ecl_request.css');

				$doc->addScript('/media/eclabs/js/ecl.js');
				$doc->addScript('/media/eclabs/js/ecl_modal.js');
				$doc->addScript('/media/eclabs/js/ecl_loader.js');
				$doc->addScript('/media/eclabs/js/ecl_request.js');
				$doc->addScript('/media/plg_system_eclabs/js/version.js');
				JHtml::_('bootstrap.framework');
				$doc->addScriptDeclaration($js);
		}
	}
}