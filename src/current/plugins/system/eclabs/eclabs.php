<?php
/**
 * @package              Econsult Labs Library
 * @subpackage           Econsult Labs system plugin
 * @version              1.0.4
 * @author               ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright            Copyright © 2023 ECL All Rights Reserved
 * @license              http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use ECLabs\Library\ECLExtension;
use ECLabs\Library\ECLInput;
use ECLabs\Library\ECLLanguage;
use ECLabs\Library\ECLPlugin;
use ECLabs\Library\ECLTools;
use ECLabs\Library\ECLUpdateInfoStatus;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Event\Table\BeforeStoreEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Installer\Administrator\Model\InstallModel;
use Joomla\Database\ParameterType;
use Joomla\Event\Event;

/**
 * @todo При обновлении библиотеки файлы удаляются, делаем проверку, чтоб в этом случае не было ошибки
 */
if(File::exists(JPATH_LIBRARIES . '/eclabs/classes/autoload.php'))
{
	if (!class_exists('simple_html_dom'))
		include_once JPATH_LIBRARIES . '/eclabs/vendor/simplehtmldom/simplehtmldom/simple_html_dom.php';


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
			$input  = $app->input;
			$option = $input->get("option", "");
			if ($option !== "com_installer")
				return;

			$this->_addMedia();

			switch (ECLVersion::getJoomlaVersion())
			{
				case '4':
					/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
					$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
					$wr = $wa->getRegistry();
					$wr->addRegistryFile('/media/plg_system_eclabs/joomla.assets.json');
					$wa->useStyle('plg_system_eclabs.eclabs4');
					break;
				default:
					$doc->addStyleSheet('/media/plg_system_eclabs/css/eclabs.css');
			}

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
		 * onAfterRender.
		 *
		 * @return  void
		 *
		 * @throws Exception
		 * @since   1.0.1
		 */
		public function onAfterRender()
		{
			$app = Factory::getApplication();
			$doc = $app->getDocument();

			// Если рендер не html, то выходим
			if (!($doc instanceof HtmlDocument))
				return;
			// Если не admin - то выходим
			if (!$app->isClient('administrator'))
				return;

			// Страница обновлений
			$input  = $app->input;
			$option = $input->get("option", "");
			if ($option !== "com_installer")
				return;

			// Вывод блока авторизации в списке обновлений
			/** @var InstallerModelUpdate $model */
			$model    = BaseDatabaseModel::getInstance('Update', 'InstallerModel');
			$items    = $model->getItems();
			$ecl_apps = new stdClass();
			foreach ($items as $item)
			{
				$ecltype = ECLExtension::checkECLType($item->extension_id);
				if ($ecltype)
				{
					$item->ecltype  = $ecltype;
					$item->version  = $item->current_version;
					$item->name     = $item->element;
					$key            = $item->update_id;
					$ecl_apps->$key = $item;
				}
			}
			// Преобразуем в массив
			$ecl_apps = json_decode(json_encode($ecl_apps), true);
			$buffer   = $app->getBody();
			$html     = new simple_html_dom();
			$html->load($buffer);
			$td_id = null;
			$table = $html->find('#installer-update .table', 0);
			if ($table)
			{
				switch (ECLVersion::getJoomlaVersion())
				{
					case '4':
						foreach ($table->find('tr[class*=row]') as $tr)
						{
							$td_id = $tr->find('td', 0);
							if ($td_id)
							{
								$i = $td_id->find('input', 0);
								if ($i)
								{
									$update_id = $i->attr['value'];
									if (isset($ecl_apps[$update_id]))
									{
										// Выводим
										$version_block = "";
										$user_data     = array('ECL' => array('user' => '', 'password' => '', 'has_token' => false, 'token' => ''));
										$update_info   = array();
										$this->onRenderVersionBlock('renderVersionBlock',
											$ecl_apps[$update_id],
											$ecl_apps[$update_id]['name'],
											!($ecl_apps[$update_id]['ecltype'] === "payed"),
											$user_data,
											$update_info,
											$version_block);

										$td_name        = $td_id->next_sibling();
										$td_name->class .= " sdi-extension-name-container";
										$t              = $td_name->last_child();
										if (is_object($t))
										{
											$str          = $t->outertext . ('<div id = "version-' . $ecl_apps[$update_id]['name'] . '" class = "sdi-version-container">' . $version_block . '</div>');
											$t->outertext = "";
											$t->outertext = $str;
										}
									}
								}
							}
						}
						break;
					default:
						foreach ($table->find('tr[class*=row]') as $tr)
						{
							$td_id = $tr->find('td', 0);
							if ($td_id)
							{
								$i = $td_id->first_child();
								if ($i && $i->tag == "input")
								{
									$update_id = $i->attr['value'];
									if (isset($ecl_apps[$update_id]))
									{
										// Выводим
										$version_block = "";
										$user_data     = array('ECL' => array('user' => '', 'password' => '', 'has_token' => false, 'token' => ''));
										$update_info   = array();
										$this->onRenderVersionBlock('renderVersionBlock',
											$ecl_apps[$update_id],
											$ecl_apps[$update_id]['name'],
											!($ecl_apps[$update_id]['ecltype'] === "payed"),
											$user_data,
											$update_info,
											$version_block);
										$td_name         = $td_id->next_sibling();
										$td_name->class  .= " sdi-extension-name-container";
										$text            = $td_name->first_child()->first_child();
										$text->outertext .= ('<div id = "version-' . $ecl_apps[$update_id]['name'] . '" class = "sdi-version-container">' . $version_block . '</div>');
									}
								}
							}
						}

				}
			}
			$html->save();
			$app->setBody((string) $html);
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
			$input  = $input = new ECLInput();
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
				ECLTools::triggerEvent('onCheckOverrides', array());
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
			$this->_logging(array('onExtensionAfterInstall' => '', 'eid' => $eid, 'installer' => $installer));
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
		 * @param $type
		 * @param $manifest
		 *
		 * @return bool
		 *
		 * @since 1.0.4
		 */
		public function onExtensionBeforeUpdate($type, $manifest): bool
		{
			$this->_logging(array('onExtensionBeforeUpdate' => '', 'type' => $type, 'manifest' => $manifest));

			return true;
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
			$this->_logging(array('onExtensionAfterUpdate' => '', 'eid' => $eid, 'installer' => $installer));
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
					$this->_logging(array('_processUpdateSites' => '', $this->_eid, (string) $attrs['name'], (string) $attrs['type'], trim($child), true, $this->_installer->extraQuery));
					$tmp = ECLExtension::generateXMLLocation($this->_eid, (string) $attrs['name'], (string) $attrs['type'], trim($child), true, $this->_installer->extraQuery);
					if ($tmp !== false)
					{
						// Вернем значение location из манифеста
						// extra_query не используется
						$this->_updateLocationAndExtraQuery(trim($child), null);
						$this->_updateSites[] = $tmp;
						$this->_logging(array('_processUpdateSites' => '', '_updateSites' => $this->_updateSites));
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
					$this->_logging(array('_processUpdateSites', Text::_('PLG_EXTENSION_JOOMLA_UNKNOWN_SITE')));
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

			$this->_logging(array("Before sending ECLExtension::getUpdateFromServer params - user_data" => $user_data));
			$update_info                    = ECLExtension::getUpdateFromServer($element, $user_data);
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
			$version             = array();
			$version['current']  = (string) $extension_info['version'];
			$version['new']      = (string) $extension_info['version'];
			$version['error']    = "";
			$vars                = new stdClass();
			$vars->show_auth_btn = true; // Показать или нет кнопку авторизации
			$vars->is_free       = $is_free;
			$vars->debug_mode    = $this->enabled_log;
			$this->getECLUpdateInfo('checkUpdate', $extension_name, $update_info, $user_data);
			$this->_logging(array('update_info', $update_info));

			switch (true)
			{
				case $is_free:
					// TODO Как получить с сервера обновлений SWJProjects информацию о бесплатном расширении
					$this->_logging(array('is free extension', $extension_name));
					$version['new']        = "";
					$vars->class           = $this->jVersion <= 3 ? "label label-success" : "alert-success";
					$vars->text            = "FREE";
					$vars->version_tooltip = "Для получения обновлений не требуется регистрация на https://econsultlab.ru";
					break;
				case !$update_info:
					// Данные не получены от сервера
					$version['error']                = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
					$vars->class                     = $this->jVersion <= 3 ? "label label-important" : "alert-danger";
					$vars->text                      = $version['error'];
					$update_info['error']['message'] = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
					$vars->version_tooltip           = Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_NEED_TOOLTIP");
					break;
				case !empty($update_info['error']) && $update_info['error'] !== ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_SUCCESS :
					// Получена ошибка от сервера обновлений
					$version['error'] = $update_info['error']['message'];
					$version['new']   = "";
					$vars->class      = $this->jVersion <= 3 ? "label label-important" : "alert-danger";;
					$vars->text            = $version['error'];
					$vars->version_tooltip = Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_NEED_TOOLTIP");
					break;
				case  !empty($update_info['token']):
					// Получен токен
					$version['new']        = $update_info['last_version'] ?? $version['current'];
					$vars->show_auth_btn   = false;
					$vars->version_tooltip = Text::_("PLG_SYSTEM_ECLABS_AUTHORISATION_SUCCESS_TOOLTIP");
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
					$version['new']        = "";
					$vars->version_tooltip = "";
					break;
			}
			if ($vars->version_tooltip)
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
					$user_data      = $input->get('user_data', array('ECL' => array('user' => '', 'password' => '', 'has_token' => false, 'token' => '')));
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
			$js         = "var ecl_jversion =" . ECLVersion::getJoomlaVersion() . ";";
			$js         .= "var ecl_enable_log=" . $this->enabled_log . ";";
			$wa_is_free = true;
			switch (ECLVersion::getJoomlaVersion())
			{
				case '4':
					/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
					$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
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
					if (!$wa->isAssetActive("style", 'bootstrap.css'))
						$wa->useStyle('bootstrap.css');

					break;
				default:
					$doc = Factory::getDocument();
					$doc->addStyleSheet('/media/eclabs/css/about.css');
					$doc->addStyleSheet('/media/eclabs/css/ecl_modal.css');
					$doc->addStyleSheet('/media/eclabs/css/ecl_request.css');
					$doc->addStyleSheet('/media/eclabs/css/ecl_loader.css');

					$doc->addScript('/media/eclabs/js/ecl.js');
					$doc->addScript('/media/eclabs/js/ecl_modal.js');
					$doc->addScript('/media/eclabs/js/ecl_loader.js');
					$doc->addScript('/media/eclabs/js/ecl_request.js');
					$doc->addScript('/media/plg_system_eclabs/js/version.js');
					JHtml::_('bootstrap.framework');
					$doc->addScriptDeclaration($js);
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

		/**
		 * Подписка на события для Joomla 4
		 *
		 * @return string[]
		 *
		 * @since 1.0.4
		 */
		public static function getSubscribedEvents(): array
		{
			return array_merge(parent::getSubscribedEvents(),
				array(
					'onAfterRender'           => 'onAfterRender',
					'onBeforeRender'          => 'onBeforeRender',
					'onExtensionAfterInstall' => 'onExtensionAfterInstall4',
					'onExtensionBeforeUpdate' => 'onExtensionBeforeUpdate4',
					'onExtensionAfterUpdate'  => 'onExtensionAfterUpdate4',
					'onRenderVersionBlock'    => 'onRenderVersionBlock4',
					'onAjaxEclabs'            => 'onAjaxEclabs',
					'onAfterRoute'            => 'onAfterRoute'
				));
		}

		/**
		 * Обработчик события для Joomla 4
		 *
		 * @param   Event  $event
		 *
		 * @return mixed|true
		 *
		 * @since 1.0.4
		 */
		public function onRenderVersionBlock4(Event $event)
		{
			return $this->_runEventHandler('onRenderVersionBlock', $event);
		}

		/**
		 * Обработчик события для Joomla 4
		 *
		 * @param   Event  $event
		 *
		 * @return mixed|true
		 *
		 * @since 1.0.4
		 */
		public function onExtensionAfterInstall4(Event $event)
		{
			return $this->_runEventHandler('onExtensionAfterInstall', $event);
		}

		/**
		 * Обработчик события для Joomla 4
		 *
		 * @param   Event  $event
		 *
		 * @return mixed|true
		 *
		 * @since 1.0.4
		 */
		public function onExtensionBeforeUpdate4(Event $event)
		{
			return $this->_runEventHandler('onExtensionBeforeUpdate', $event);
		}

		/**
		 * Обработчик события для Joomla 4
		 *
		 * @param   Event  $event
		 *
		 * @return mixed|true
		 *
		 * @since 1.0.4
		 */
		public function onExtensionAfterUpdate4(Event $event)
		{
			return $this->_runEventHandler('onExtensionAfterUpdate', $event);
		}
	}
}