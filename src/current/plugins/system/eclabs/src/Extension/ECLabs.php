<?php
/**
 * @package              Econsult Labs Library
 * @subpackage           Econsult Labs system plugin
 * @version              __DEPLOYMENT_VERSION__
 * @author               ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright            Copyright © 2025 ECL All Rights Reserved
 * @license              http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace Joomla\Plugin\System\ECLabs\Extension;

\defined('_JEXEC') or die;

use ECLabs\Library\ECLExtension;
use ECLabs\Library\ECLInput;
use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLLogging\ECLLogging;
use ECLabs\Library\ECLPlugin;
use ECLabs\Library\ECLUpdateInfoStatus;
use ECLabs\Library\ECLVersion;
use ECLabs\Library\Helpers\PluginsHelper;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Extension\AfterInstallEvent;
use Joomla\CMS\Event\Extension\AfterUpdateEvent;
use Joomla\CMS\Event\Extension\BeforeUpdateEvent;
use Joomla\CMS\Event\View\DisplayEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use stdClass;

/**
 * @package              Econsult Labs Library
 * @subpackage           Econsult Labs system plugin
 * @version              1.0.1
 * @author               ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright            Copyright © 2025 ECL All Rights Reserved
 * @license              http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

/**
 * @todo При обновлении библиотеки файлы удаляются, делаем проверку, чтоб в этом случае не было ошибки
 */
if (file_exists(JPATH_LIBRARIES . '/eclabs/src/autoload.php'))
{
	if (!class_exists('simple_html_dom'))
		include_once JPATH_LIBRARIES . '/eclabs/vendor/simplehtmldom/simplehtmldom/simple_html_dom.php';


	require_once JPATH_LIBRARIES . '/eclabs/src/autoload.php';

	ECLLanguage::loadExtraLanguageFiles('plg_system_eclabs', JPATH_ADMINISTRATOR);

	final class ECLabs extends ECLPlugin
	{

		/**
		 * Load the language file on instantiation.
		 *
		 * @var    bool
		 *
		 * @since  1.0.0
		 */
		protected $autoloadLanguage = true;
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
		 * @param   DispatcherInterface      $dispatcher
		 * @param   array                    $config
		 * @param   CMSApplicationInterface  $app
		 * @param   DatabaseInterface        $db
		 *
		 * @since       2.0.0
		 */

		public function __construct(DispatcherInterface $dispatcher, array $config, CMSApplicationInterface $app, DatabaseInterface $db)
		{
			parent::__construct($dispatcher, $config,$app,$db);

			$this->enabled_log  = $this->params->get('logging', false);
			$this->_plugin_path = __DIR__;
		}

		/**
		 * Returns an array of events this subscriber will listen to.
		 *
		 * @return  array
		 *
		 * @since   1.0.0
		 */
		public static function getSubscribedEvents(): array
		{
			return [
				'onBeforeRender'          => 'onBeforeRender',
				'onExtensionAfterInstall' => 'onExtensionAfterInstall',
				'onExtensionBeforeUpdate' => 'onExtensionBeforeUpdate',
				'onExtensionAfterUpdate'  => 'onExtensionAfterUpdate',
				'onRenderVersionBlock'    => 'onRenderVersionBlock',
				'onAjaxEclabs'            => 'onAjaxEclabs',
				'onAfterRoute'            => 'onAfterRoute',
				'onBeforeDisplay'         => 'onBeforeDisplay'
			];
		}


		/**
		 * Обработчик события onBeforeDisplay
		 *
		 * @param   DisplayEvent  $event
		 *
		 * @return void
		 * @throws Exception
		 * @since 2.0.0
		 */
		public function onBeforeDisplay(DisplayEvent $event): void
		{
			/** @var HtmlView $subject */
			$subject = $event->getArgument('subject');
			//var_dump($event->getArgument('extension'));
			if ($event->getArgument('extension') === "com_installer.updatesites")
			{
				$items = $subject->get("items");
				// Удаляем в списке серверов обновления для расширений ECL с бесплатным обновлением бейдж с ключом
				if (is_array($items))
				{
					foreach ($items as $i => $item)
					{

						if ($item->extension_id && ECLExtension::checkECLType($item->extension_id) === ECLExtension::_ECL_EXTENSION_TYPE_FREE)
						{
							$item->downloadKey['supported'] = false;
						}
					}
					$subject->set("items", $items);
					$event->setArgument('subject', $subject);
				}
			}
			elseif ($event->getArgument('extension') === "com_installer.updatesite")
			{
				// Удаляем поле ключа в форме сервера обновлений для бесплатного расширения ECL
				$item = $subject->get("item");
				if($item)
				{
					if ($item->update_site_id && ECLExtension::checkECLTypeByUpdateSiteId($item->update_site_id) === ECLExtension::_ECL_EXTENSION_TYPE_FREE)
					{
						$subject->getForm()->removeField('extra_query');
						$event->setArgument('subject', $subject);
					}
				}
			}
		}

		/**
		 * Обработка события.
		 * Производится обновление таблицы #__update_sites информацией о платных расширениях ECL
		 * Информация формируется в событиях onExtensionAfterInstall и onExtensionAfterUpdate
		 *
		 * @throws Exception
		 * @since 1.0.0
		 */
		public function onBeforeRender(): void
		{
			$doc = $this->getApplication()->getDocument();

			// Если рендер не html, то выходим
			if (!($doc instanceof HtmlDocument))
				return;
			// Если не admin - то выходим
			if (!$this->getApplication()->isClient('administrator'))
				return;

			$this->_addMedia();

			$input  = new ECLInput(false);
			$option = $input->get("option", "");
			if ($option !== "com_installer")
				return;

			/** @var WebAssetManager $wa */
			$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
			$wr = $wa->getRegistry();
			$wr->addRegistryFile('/media/plg_system_eclabs/joomla.assets.json');
			$wa->useStyle('plg_system_eclabs.eclabs4');

			$this->_updateSites = Factory::getApplication()->getUserState('eclabs.updateSites', false);
			$this->_addLog(ECLLogging::INFO, 'eclabs.updateSites', $this->_updateSites);
			if (is_array($this->_updateSites))
			{
				//var_dump($this->_updateSites);
				ECLExtension::updateXMLLocation($this->_updateSites);
				Factory::getApplication()->setUserState('eclabs.updateSites', null);
			}
		}

		/**
		 * onAfterRoute.
		 *
		 * @return  void
		 *
		 * @throws Exception
		 * @since   1.0.5
		 */
		public function onAfterRoute()
		{
			// TODO сделать в версии 1.0.5
			$input  = new ECLInput(false);
			$option = $input->get('option', '');
			$view   = $input->get('view', '');
			$task   = $input->get('task', '');
			$layout = $input->get('layout', '');
			// Если была установка или обновление joomla, расширений,
			// то проверяем переопределения
			if (($option == 'com_joomlaupdate' && $task == 'update.install') ||
				($option == 'com_installer' && $task == 'install.install') ||
				($option == 'com_installer' && $task == 'update.update') ||
				($option == 'com_joomlaupdate' && $layout == 'complete'))
			{
				PluginsHelper::triggerPlugins(["system"], 'onCheckOverrides');
			}
		}

		/**
		 * Формируем информацию для платных расширений ECL после установки
		 *
		 * @param   AfterInstallEvent  $event
		 *
		 * @throws Exception
		 * @since 1.0.0
		 */
		public function onExtensionAfterInstall(AfterInstallEvent $event): void
		{
			$installer = $event->getInstaller();
			$eid       = $event->getEid();

			$this->_addLog(ECLLogging::INFO, 'eid', $eid);
			if ($eid)
			{
				$this->_installer = $installer;
				$this->_eid       = (int) $eid;

				// Handle any update sites
				$this->_processUpdateSites();
			}
		}

		/**
		 * Обработчик события до обновления расширения
		 *
		 * @param   BeforeUpdateEvent  $event
		 *
		 * @return bool
		 *
		 * @since 1.0.4
		 */
		public function onExtensionBeforeUpdate(BeforeUpdateEvent $event): bool
		{
			$type     = $event->getType();
			$manifest = $event->getManifest();

			$this->_addLog(ECLLogging::INFO, 'type', $type);
			$this->_addLog(ECLLogging::INFO, 'manifest', $manifest);

			return true;
		}

		/**
		 * Формируем информацию для платных расширений ECL после обновления или перестроения серверов обновлений
		 *
		 * @param   AfterUpdateEvent  $event
		 *
		 * @throws Exception
		 * @since 1.0.0
		 */
		public function onExtensionAfterUpdate(AfterUpdateEvent $event): void
		{
			$installer = $event->getInstaller();
			$eid       = $event->getEid();
			$this->_addLog(ECLLogging::INFO, 'eid', $eid);

			if ($eid)
			{
				$this->_installer = $installer;
				$this->_eid       = (int) $eid;

				// Handle any update sites
				$this->_processUpdateSites();
			}
		}

		/**
		 * Обработка обращений по AJAX
		 * @return void
		 * @throws Exception
		 * @since 1.0.0
		 */
		#[NoReturn] public function onAjaxEclabs(): void
		{
			$input  = new ECLInput(true);
			$action = $input->get('action', '');
			$this->_addLog(ECLLogging::INFO, 'action', $action);
			$out = array('ok' => '', 'response' => '');

			switch ($action)
			{
				case "renderVersionBlock":
					$extension_info = $input->get('extension_info', '');
					$element_name   = $input->get('element_name', '');
					$user_data      = $input->get('user_data', array('ECL' => array('user' => '', 'password' => '', 'has_token' => false, 'token' => '')), 'array');
					$is_free        = $input->get('is_free', 0);
					$this->_addLog(ECLLogging::INFO, 'element_name', $element_name);
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
			ECLLanguage::loadLibLanguage();
			$version                 = array();
			$version['current']      = (string) $extension_info['version'];
			$version['new']          = (string) $extension_info['version'];
			$version['container_id'] = "version-" . $extension_info['name'];
			$version['error']        = "";
			$vars                    = new stdClass();
			$vars->show_auth_btn     = true; // Показать или нет кнопку авторизации
			$vars->is_free           = $is_free;
			$vars->debug_mode        = $this->enabled_log;
			$this->getECLUpdateInfo('checkUpdate', $extension_name, $update_info, $user_data);
			$this->_addLog(ECLLogging::INFO, 'update_info', $update_info);
			switch (true)
			{
				case $is_free:
					// TODO Как получить с сервера обновлений SWJProjects информацию о бесплатном расширении
					$this->_addLog(ECLLogging::INFO, 'is free extension', $extension_name);
					$version['new']        = "";
					$vars->class           = $this->jVersion <= 3 ? "label label-success" : "alert-success bg-success";
					$vars->text            = "FREE";
					$vars->version_tooltip = "Для получения обновлений не требуется регистрация на https://econsultlab.ru";
					break;
				case !$update_info:
					// Данные не получены от сервера
					$version['error']                = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
					$vars->class                     = $this->jVersion <= 3 ? "label label-important" : "alert-danger bg-danger";
					$vars->text                      = $version['error'];
					$update_info['error']['message'] = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
					$vars->version_tooltip           = Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_NEED_TOOLTIP");
					break;
				case !empty($update_info['error']) && $update_info['error'] !== ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_SUCCESS :
					// Получена ошибка от сервера обновлений
					$version['error'] = $update_info['error']['message'];
					$version['new']   = "";
					$vars->class      = $this->jVersion <= 3 ? "label label-important" : "alert-danger bg-danger";;
					$vars->text            = $version['error'];
					$vars->version_tooltip = Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_NEED_TOOLTIP");
					break;
				case  !empty($update_info['token']):
					// Получен токен
					$version['new']        = $update_info['last_version'] ?? $version['current'];
					$vars->show_auth_btn   = false;
					$vars->version_tooltip = Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_SUCCESS_TOOLTIP");
					if (version_compare($version['current'], $version['new'], '>='))
					{
						$version['new'] = "";
						$vars->class    = $this->jVersion <= 3 ? "label label-success" : "alert-success bg-success";
						$vars->text     = Text::_('ECLABS_ABOUT_FIELD_USED_LAST_VERSION');
					}
					else
					{
						$vars->class = $this->jVersion <= 3 ? "label label-warning" : "alert-warning bg-warning";
						$vars->text  = Text::_('ECLABS_ABOUT_FIELD_NEW_VERSION');
					}
					break;
				default:
					$version['new']        = "";
					$vars->version_tooltip = "";
					break;
			}
			if ($vars->version_tooltip && $this->jVersion <= 3)
			{
				$vars->class .= " hasTooltip";
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
		 * Processes the list of update sites for an extension.
		 *
		 * @return  void
		 *
		 * @throws Exception
		 * @since   1.0.0
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
					$this->_addLog(ECLLogging::INFO, 'eid', $this->_eid);
					$this->_addLog(ECLLogging::INFO, 'attrs[\'name\']', (string) $attrs['name']);
					$this->_addLog(ECLLogging::INFO, 'attrs[\'type\']', (string) $attrs['type']);
					$this->_addLog(ECLLogging::INFO, 'child', trim($child));
					$this->_addLog(ECLLogging::INFO, '_installer->extraQuery', $this->_installer->extraQuery);
					$tmp = ECLExtension::generateXMLLocation($this->_eid, (string) $attrs['name'], (string) $attrs['type'], trim($child), true, $this->_installer->extraQuery);
					if ($tmp !== false)
					{
						// Вернем значение location из манифеста
						// extra_query не используется
						$this->_updateLocationAndExtraQuery(trim($child), null);
						$this->_updateSites[] = $tmp;
						$this->_addLog(ECLLogging::INFO, '_updateSites', $this->_updateSites);
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
					$this->_addLog(ECLLogging::WARNING, Text::_('PLG_EXTENSION_JOOMLA_UNKNOWN_SITE'));
				}
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
		private function _updateLocationAndExtraQuery(string $location, ?string $extra_query): void
		{
			$update_site_id = ECLExtension::getUpdateSiteId($this->_eid);
			ECLExtension::updateECLLocationAndExtraQuery($update_site_id, $location, $extra_query);
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
			if ((($user_data['ECL']['has_token'] ?? false) && empty($user_data['ECL']['token']) &&
					(empty($user_data['ECL']['user']) || empty($user_data['ECL']['password']))) ||
				(!($user_data['ECL']['has_token'] ?? false)) && (empty($user_data['ECL']['user']) || empty($user_data['ECL']['password'])))
			{
				// Данных для авторизации не введено. Получим их из БД
				$user_data = ECLExtension::getCustomData($element_name);
			}

			switch (true)
			{
				case(!isset($user_data['ECL'])):
				case (empty($user_data['ECL']['user']) || empty($user_data['ECL']['password'])) && !($user_data['ECL']['has_token'] ?? false):
					$update_info['error'] = array(
						'code'    => ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING,
						'message' => ECLUpdateInfoStatus::getEnumNameText(ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING)
					);

					return;
				case((($user_data['ECL']['has_token'] ?? false) && $user_data['ECL']['token'])) || ($user_data['ECL']['user'] && $user_data['ECL']['password']):
					break;
				case ($user_data['ECL']['has_token'] ?? false) && empty($user_data['ECL']['token']):
					$update_info['error'] = array(
						'code'    => ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_ERROR_MISSING_TOKEN,
						'message' => ECLUpdateInfoStatus::getEnumNameText(ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_ERROR_MISSING_TOKEN)
					);

					return;
			}

			$element = ECLExtension::getElement($element_name);

			$this->_addLog(ECLLogging::INFO, 'Before sending ECLExtension::getUpdateFromServer params - user_data', $user_data);
			$update_info                    = ECLExtension::getUpdateFromServer($element, $user_data);
			$user_data['ECL']['token']      = $update_info['token'] ?? '';
			$user_data['ECL']['project_id'] = $update_info['project_id'] ?? '';
			ECLExtension::setCustomData($element_name, $user_data['ECL']);
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

			$wa_is_free = true;

			/** @var WebAssetManager $wa */
			$wa = $this->getApplication()->getDocument()->getWebAssetManager();
			$wr = $wa->getRegistry();
			$wr->addRegistryFile('/media/eclabs/joomla.assets.json');
			$wr->addRegistryFile('/media/plg_system_eclabs/joomla.assets.json');

			if (!$wa->isAssetActive("style", 'eclabs.request'))
			{
				$wa->useStyle('eclabs.request');
				$wa->addInlineScript($js);
			}
			else
			{
				$wa_is_free = false;
			}

			if (!$wa->isAssetActive("script", 'eclabs.request'))
				$wa->useScript('eclabs.request');
			if (!$wa->isAssetActive("style", 'eclabs.loader'))
				$wa->useStyle('eclabs.loader');
			if (!$wa->isAssetActive("script", 'eclabs.loader'))
				$wa->useScript('eclabs.loader');
			if (!$wa->isAssetActive("style", 'eclabs.modal'))
				$wa->useStyle('eclabs.modal');
			if (!$wa->isAssetActive("script", 'eclabs.modal'))
				$wa->useScript('eclabs.modal');
			if (!$wa->isAssetActive("script", 'plg_system_eclabs.version'))
				$wa->useScript('plg_system_eclabs.version');
			if (!$wa->isAssetActive("script", 'bootstrap.modal'))
				$wa->useScript('bootstrap.modal');
			if ($this->params->get('load_bootstrap', false))
			{
				if (!$wa->isAssetActive("style", 'bootstrap.css'))
					$wa->useStyle('bootstrap.css');
			}

			if (!$wa_is_free)
				return;

			ECLLanguage::loadLibLanguage();
			Text::script('JCLOSE');
			Text::script('JAPPLY');
			Text::script('JVERSION');
			Text::script('ECLUPDATEINFO_STATUS_SUCCESS_TEXT');
			Text::script('ECLUPDATEINFO_STATUS_ERROR_AUTHORIZATION_TEXT');
			Text::script('ECLUPDATEINFO_STATUS_ERROR_MISSING_VERSION_TEXT');
			Text::script('ECLUPDATEINFO_STATUS_ERROR_MISSING_EXTENSION_TEXT');
			Text::script('ECLUPDATEINFO_STATUS_ERROR_USERINFO_MISSING_TEXT');
			Text::script('ECLUPDATEINFO_STATUS_ERROR_MISSING_TOKEN_TEXT');
		}
	}
}
