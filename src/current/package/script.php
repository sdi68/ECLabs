<?php defined('_JEXEC') or die;

/**
 * @package        Econsult Labs Library package
 * @version          1.0.5
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\Manifest\PackageManifest as JPackageManifest;

if (!class_exists('pkg_eclabsInstallerScript'))
{
	/**
	 * Class pkg_ECLabsInstallerScript
	 * @since 1.0.5
	 */
	class pkg_eclabsInstallerScript
	{
		/**
		 * Адаптер установщика
		 * @var object
		 * @since 1.0.4
		 */
		static $parent = null;
		/**
		 * Текущая версия устанавливаемого элемента
		 * @var string
		 * @since 1.0.4
		 */
		static $current_version = "";
		/**
		 * Наименование устанавливаемого элемента
		 * @var null
		 * @since 1.0.4
		 */
		static $name = "";
		/**
		 * Предыдущая версия устанавливаемого элемента
		 * @var string
		 * @since 1.0.4
		 */
		static $previous_version = "";
		/**
		 * Зависимости устанавливаемого элемента
		 * @var array
		 * @since 1.0.4
		 */
		static $dependencies = array();

		/**
		 * Method to check compatible.
		 *
		 * @param string $type Type of PostFlight action.
		 * @param InstallerAdapter $parent Parent object calling object.
		 *
		 * @return  boolean  Compatible current version or not.
		 *
		 * @throws Exception
		 * @since 1.0.4
		 */
		public function preflight(string $type, InstallerAdapter $parent): bool
		{

			$manifest = $parent->getManifest();


			if (!in_array($type, ['install', 'update'])) {
				return true;
			}

			if (!self::checkCompatible()) {
				return false;
			}

			static::$parent = $parent;
			static::$name = trim($manifest->name);
			static::$current_version = trim($manifest->version);
			static::$previous_version = self::getPreviousVersion();

			if (count(static::$dependencies)) {
				return self::installDependencies();
			}
			return true;
		}

		/**
		 * Runs right after any installation action.
		 *
		 * @param string $type Type of PostFlight action. Possible values are:
		 * @param InstallerAdapter $parent Parent object calling object.
		 * @return  boolean  True on success
		 * @since  1.4.0
		 */
		public function postflight(string $type, InstallerAdapter $parent)
		{

			if ($type == 'uninstall') {
				return true;
			}

			?>
			<style type="text/css">
                .version-history {
                    margin: 0 0 2em 0;
                    padding: 0;
                    list-style-type: none;
                }

                .version-history > li {
                    margin: 0 0 0.5em 0;
                    padding: 0 0 0 4em;
                    text-align: left;
                    font-weight: normal;
                }

                .version-new,
                .version-fixed,
                .version-upgraded {
                    float: left;
                    font-size: 0.8em;
                    margin-left: -4.9em;
                    width: 4.5em;
                    color: white;
                    text-align: center;
                    font-weight: bold;
                    text-transform: uppercase;
                    -webkit-border-radius: 4px;
                    -moz-border-radius: 4px;
                    border-radius: 4px;
                }

                .version-new {
                    background: #7dc35b;
                }

                .version-fixed {
                    background: #e9a130;
                }

                .version-upgraded {
                    background: #61b3de;
                }
			</style>

			<h3><?php echo Text::_('PKG_ECLABS_CHANGE_LOG_TITLE');?></h3>
			<ul class="version-history">
				<li><span class="version-upgraded">1.0.6</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_1_0_6');?></li>
                <li><span class="version-fixed">1.0.5</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_1_0_6');?></li>
			</ul>
			<div style="clear: both;"></div>
			<?php
			return true;
		}

		/**
		 * Install all dependencies for this element
		 * @throws Exception
		 * @since  1.4.0
		 */
		private static function installDependencies(): bool
		{

			if (count(static::$dependencies)) {
				foreach (static::$dependencies as $dependency) {
					$info = self::getDependencyInfo($dependency);
					//var_export($info);
					if ($info) {
						if (self::needDependencyInstall($info)) {
							return self::installDependency($info['downloads']['downloadurl']);
						}
					}
				}

			}
			return true;
		}

		/**
		 * Get dependency info from update server
		 * @param array $dependency Dependency
		 * @return array | false (if unsuccessful)
		 * @throws Exception
		 * @since  1.4.0
		 */
		private static function getDependencyInfo(array $dependency): array|false
		{
			$app = Factory::getApplication();
			$version = new Version;
			$httpOption = new Registry;
			$httpOption->set('userAgent', $version->getUserAgent('Joomla', true, false));

			// JHttp transport throws an exception when there's no response.
			try {
				$http = HttpFactory::getHttp($httpOption);
				$response = $http->get($dependency['url'], array(), 20);
			} catch (\RuntimeException $e) {
				$response = null;
			}

			if ($response === null || $response->code !== 200) {
				$app->enqueueMessage(Text::sprintf('COM_INSTALLER_MSG_ERROR_CANT_CONNECT_TO_UPDATESERVER', $dependency['url']), 'warning');
				return false;
			}
			// Выбираем нужную секцию манифеста обновления в зависимости от версии Joomla
			$version_mask = '3.';
			switch (true) {
				case $version->isCompatible('5.0'):
					$version_mask = '5.';
					break;
				case $version->isCompatible('4.0'):
					$version_mask = '4.';
					break;
				case $version->isCompatible('3.0'):
				default:
			}

			$xml = new SimpleXMLElement($response->body);
			foreach ($xml->update as $item) {
				//var_export($item->targetplatform);
				$tp = $item->targetplatform;
				if (str_contains((string)($tp->attributes()->version), $version_mask)) {
					return json_decode(json_encode($item), true);
				}
			}

			return false;
		}

		/**
		 * Check all compatibilities requirements for element
		 * @return bool true if compatible
		 * @throws Exception
		 * @since 1.0.4
		 */
		private static function checkCompatible(): bool
		{
			$app = Factory::getApplication();
			$jversion = new Version();
			if (!$jversion->isCompatible('3.0.0')) {
				$app->enqueueMessage(Text::_('PKG_ECLABS_ERROR_COMPATIBLE_JOOMLA_3'), 'error');
				return false;
			}
			return true;
		}

		/**
		 * Get previous version for element
		 * @return string
		 * @since 1.0.4
		 */
		private static function getPreviousVersion(): string
		{

			$xml_file = self::getXmlFile();

			if (!$xml_file) {
				return '';
			}

			$manifest = new JPackageManifest($xml_file);

			return isset($manifest->version) ? trim($manifest->version) : '';
		}

		/**
		 * Get path for manifest file
		 * @return string
		 * @since 1.0.4
		 */
		private static function getXmlFile()
		{
			$xml_file = JPATH_MANIFESTS . '/packages/pkg_' . static::$name . '.xml';

			if (file_exists($xml_file)) {
				return $xml_file;
			}

			$xml_file = JPATH_LIBRARIES . '/' . static::$name . '.xml';

			if (file_exists($xml_file)) {
				return $xml_file;
			}

			$xml_file = JPATH_ADMINISTRATOR . '/components/com_' . static::$name . '/' . static::$name . '.xml';

			if (file_exists($xml_file)) {
				return $xml_file;
			}

			return '';
		}

		/**
		 * Проверяет возможность и необходимость установки расширения
		 * @param array $info Параметры устанавливаемого расширения
		 *
		 * @return bool
		 *
		 * @since 1.0.4
		 */
		private static function needDependencyInstall(array $info): bool
		{
			// Получаем информацию об установленном расширении
			$db = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('manifest_cache'))
				->from($db->qn('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote($info['type']))
				->where($db->quoteName('element') . ' = ' . $db->quote($info['element']));
			$params = json_decode($db->setQuery($query)->loadResult() ?? "", true);
			$ret = false;
			if (is_null($params)) {
				// Расширение не установлено. Надо устанавливать.
				$ret = true;
			} else if (is_array($params)) {
				// Если на сайте более старая версия, то нужно устанавливать расширение
				$ret = (bool)version_compare($info['version'], $params['version'], '>');
			}
			return $ret;
		}

		/**
		 * Устанавливает расширение по URL
		 * @param string $url URL сервера обновлений расширения
		 *
		 * @return bool
		 *
		 * @throws Exception
		 * @since 1.0.4
		 */
		private static function installDependency(string $url): bool
		{
			// Load installer plugins for assistance if required:
			PluginHelper::importPlugin('installer');

			$app = Factory::getApplication();

			$package = null;

			// This event allows an input pre-treatment, a custom pre-packing or custom installation.
			// (e.g. from a JSON description).
			$results = $app->triggerEvent('onInstallerBeforeInstallation', array(static::$parent, &$package));

			if (in_array(true, $results, true)) {
				return true;
			}

			if (in_array(false, $results, true)) {
				return false;
			}

			// Download the package at the URL given.
			$p_file = JInstallerHelper::downloadPackage($url);

			// Was the package downloaded?
			if (!$p_file) {
				$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

				return false;
			}

			$config = Factory::getConfig();
			$tmp_dest = $config->get('tmp_path');

			// Unpack the downloaded package file.
			$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

			// This event allows a custom installation of the package or a customization of the package:
			$results = $app->triggerEvent('onInstallerBeforeInstaller', array(static::$parent, &$package));

			if (in_array(true, $results, true)) {
				return true;
			}

			if (in_array(false, $results, true)) {
				JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

				return false;
			}

			// Get an installer instance.
			$installer = new Installer();

			/*
			 * Check for a Joomla core package.
			 * To do this we need to set the source path to find the manifest (the same first step as JInstaller::install())
			 *
			 * This must be done before the unpacked check because JInstallerHelper::detectType() returns a boolean false since the manifest
			 * can't be found in the expected location.
			 */
			if (is_array($package) && isset($package['dir']) && is_dir($package['dir'])) {
				$installer->setPath('source', $package['dir']);

				if (!$installer->findManifest()) {
					JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
					$app->enqueueMessage(Text::sprintf('COM_INSTALLER_INSTALL_ERROR', '.'), 'warning');

					return false;
				}
			}

			// Was the package unpacked?
			if (!$package || !$package['type']) {
				JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
				$app->enqueueMessage(Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

				return false;
			}

			// Install the package.
			if (!$installer->install($package['dir'])) {
				// There was an error installing the package.
				$msg = Text::sprintf('COM_INSTALLER_INSTALL_ERROR',
					Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
				$result = false;
				$msgType = 'error';
			} else {
				// Package installed successfully.
				$msg = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS',
					Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
				$result = true;
				$msgType = 'message';
			}

			// This event allows a custom a post-flight:
			$app->triggerEvent('onInstallerAfterInstaller', array(self::$parent, &$package, $installer, &$result, &$msg));

			$app->enqueueMessage($msg, $msgType);

			// Cleanup the install files.
			if (!is_file($package['packagefile'])) {
				$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
			}

			JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			return $result;
		}

	}
}
