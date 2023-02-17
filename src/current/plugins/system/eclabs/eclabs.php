<?php
/**
 * @package         Econsult Labs Library
 * @subpackage      Econsult Labs system plugin
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use ECLabs\Library\ECLExtension;
use ECLabs\Library\ECLPlugin;
use ECLabs\Library\ECLUpdateInfoStatus;
use ECLabs\Library\ECLVersion;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

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
	 * @param          $subject
	 * @param   array  $config
	 * @since       1.0.0
	 */
	public function __construct(&$subject, array $config = array())
	{
		parent::__construct($subject, $config);
		$this->enabled_log  = $this->params->get('logging', false);
		$this->_plugin_path = __DIR__;
	}

	/**
	 * Получение информации по расширению от сервера обновлений
	 *
	 * @param   string  $context    Контекст вызова (не используется)
	 * @param   string  $element_name   Имя расширения
	 * @param   array   $update_info    Массив для хранения информации по расширению
	 * @param   array   $user_data      Массив информации о пользователе сервера обновлений
	 *
	 * @return void
	 *
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

				return ;
			}
		}
		$element                   = ECLExtension::getElement($element_name);
		$update_info               = ECLExtension::checkUpdate($element, $user_data['ECL']['user'], $user_data['ECL']['password']);
		$user_data['ECL']['token'] = $update_info['token'] ?? '';
		ECLExtension::setCustomData($element_name, $user_data['ECL']);
		}

	/**
	 * Формирует блок версии для xml поля about расширения для текущего пользователя сервера обновлений.
	 * @param   string  $context
	 * @param   array   $extension_info
	 * @param   string  $extension_name
	 * @param   array   $user_data
	 * @param   array   $update_info
	 * @param   string  $html
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 1.0.0
	 */
	public function onRenderVersionBlock(string $context, array $extension_info, string $extension_name, array $user_data, array &$update_info, string &$html): bool
	{
		// TODO проверка контекста
		if (!($context == "about" || $context == 'renderVersionBlock'))
			return false;

		//$update_info = array();
		$this->getECLUpdateInfo('checkUpdate', $extension_name, $update_info, $user_data);
		$this->_logging(array('update_info', $update_info));
		$version            = array();
		$version['current'] = (string) $extension_info['version'];
		$version['new']     = (string) $extension_info['version'];
		$version['error']   = "";
		$vars               = new stdClass();
		switch (true)
		{
			case !$update_info:
				// Данные не получены от сервера
				$version['error']                = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
				$vars->class                     = "alert-danger";
				$vars->text                      = $version['error'];
				$update_info['error']['message'] = Text::_("ECLABS_ABOUT_FIELD_ERROR_NOT_RESPONSE");
				break;
			case !empty($update_info['error']) && $update_info['error'] !== ECLUpdateInfoStatus::ECLUPDATEINFO_STATUS_SUCCESS :
				// Получена ошибка от сервера обновлений
				$version['error'] = $update_info['error']['message'];
				$version['new']   = "";
				$vars->class      = "alert-danger";
				$vars->text       = $version['error'];
				break;
			case  !empty($update_info['token']):
				// Получен токен
				$version['new'] = $update_info['last_version'] ?? $version['current'];
				if ($version['new'] == $version['current'])
				{
					$version['new'] = "";
					$vars->class    = "alert-success";
					$vars->text     = Text::_('ECLABS_ABOUT_FIELD_USED_LAST_VERSION');
				}
				else
				{
					$vars->class = "alert-warning";
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
		$input    = Factory::getApplication()->getInput();
		$action   = $input->get('action', '');
		$alt_data = null; // Если при POST данные не передаются через REQUEST
		if (empty($action))
		{
			$json     = file_get_contents('php://input');
			$alt_data = json_decode($json, true);
			$action   = $alt_data['action'] ?? '';
		}
		$this->_logging(array('action', $action));
		$out = array('ok' => '', 'response' => '');

		switch ($action)
		{
			case "renderVersionBlock":
				if (is_null($alt_data))
				{
					$extension_info = $input->get('extension_info', '');
					$element_name   = $input->get('element_name', '');
					$user_data      = $input->get('user_data', array('ECL' => array('user' => '', 'password' => '')));
				}
				else
				{
					$extension_info = $alt_data['extension_info'] ?? '';
					$element_name   = $alt_data['element_name'] ?? '';
					$user_data      = $alt_data['user_data'] ?? array('ECL' => array('user' => '', 'password' => ''));
				}
				$this->_logging(array('element_name', $element_name));
				$html        = "";
				$update_info = array();
				$this->onRenderVersionBlock('renderVersionBlock', $extension_info, $element_name, $user_data, $update_info, $html);
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
				break;
			default:
				$doc = JFactory::getDocument();
				$doc->addStyleSheet('/media/eclabs/css/about.css');
				$doc->addStyleSheet('/media/eclabs/css/modal.css');
				$doc->addStyleSheet('/media/eclabs/css/request.css');
				$doc->addScript('/media/eclabs/js/ecl.js');
				$doc->addScript('/media/eclabs/js/ecl_modal.js');
				$doc->addScript('/media/eclabs/js/ecl_request.js');
				JHtml::_('bootstrap.framework');
				JHtml::_('bootstrap.loadCss', true);
		}
	}
}