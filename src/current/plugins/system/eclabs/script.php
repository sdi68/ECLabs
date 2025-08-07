<?php \defined('_JEXEC') or die;

/**
 * @package         Econsult Labs Library
 * @subpackage   Econsult Labs system plugin
 * @version           __DEPLOYMENT_VERSION__
 * @author            ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Event\Installer\AfterInstallerEvent;
use Joomla\CMS\Event\Installer\BeforeInstallationEvent;
use Joomla\CMS\Event\Installer\BeforeInstallerEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\Manifest\PackageManifest as JPackageManifest;
use Joomla\Filesystem\Path;

if (!class_exists('plgSystemECLabsInstallerScript'))
{
	/**
	 * Class ECLabsSystemPluginInstallerScript
	 * @since 1.0.0
	 */
	class plgSystemECLabsInstallerScript
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
		 * Extension files.
		 *
		 * @var  array
		 *
		 * @since  1.0.0
		 */
		protected array $externalFiles = [
			[
				//'src'  => JPATH_ROOT . '/modules/mod_radicalmart_extfilter/tmpl/radicalmart_extfilter_ajax.php',
				//'dest' => JPATH_ROOT . '/templates/system/radicalmart_extfilter_ajax.php',
				//'type' => 'file',
			],
            ];

		/**
		 * Method to copy external files.
		 *
		 * @param   Installer  $installer  Installer calling object.
		 *
		 * @return  bool True on success, False on failure.
		 *
		 * @since  1.0.0
		 */
		public function copyExternalFiles(Installer $installer): bool
		{
			$copyFiles = [];
			foreach ($this->externalFiles as $path)
			{
                if($path['src'] && $path['dest'])
                {
	                $path['src']  = Path::clean($path['src']);
	                $path['dest'] = Path::clean($path['dest']);
	                if (basename($path['dest']) !== $path['dest'])
	                {
		                $newdir = dirname($path['dest']);
		                if (!Folder::create($newdir))
		                {
			                Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

			                return false;
		                }
	                }

	                $copyFiles[] = $path;
                }
			}

			return $installer->copyFiles($copyFiles, true);
		}

		/**
		 * Method to delete external files.
		 *
		 * @return  bool  True on success.
		 *
		 * @since  1.0.0
		 */
		protected function removeExternalFiles(): bool
		{
			// Process each file in the $files array (children of $tagName).
			foreach ($this->externalFiles as $path)
			{
				// Actually delete the files/folders
				if (is_dir($path['dest']))
				{
					$val = Folder::delete($path['dest']);
				}
				else
				{
					$val = File::delete($path['dest']);
				}

				if ($val === false)
				{
					Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');

					return false;
				}
			}

			return true;
		}

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
			//var_export($manifest);

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
		 * Method to parse through a layout element of the installation manifest and take appropriate action.
		 *
		 * @param   SimpleXMLElement  $element    The XML node to process.
		 * @param   Installer         $installer  Installer calling object.
		 *
		 * @return  boolean  True on success.
		 *
		 * @since  1.0.0
		 */
		protected function parseLayouts(SimpleXMLElement $element, $installer)
		{
			if (!$element || !count($element->children())) return false;

			// Get destination
			$folder      = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
			$destination = Path::clean(JPATH_ROOT . '/layouts' . $folder);

			// Get source
			$folder = (string) $element->attributes()->folder;
			$source = ($folder && file_exists($installer->getPath('source') . '/' . $folder)) ?
				$installer->getPath('source') . '/' . $folder : $installer->getPath('source');

			// Prepare files
			$copyFiles = array();
			foreach ($element->children() as $file)
			{
				$path['src']  = Path::clean($source . '/' . $file);
				$path['dest'] = Path::clean($destination . '/' . $file);

				// Is this path a file or folder?
				$path['type'] = $file->getName() === 'folder' ? 'folder' : 'file';
				if (basename($path['dest']) !== $path['dest'])
				{
					$newdir = dirname($path['dest']);
					if (!Folder::create($newdir))
					{
						Log::add(Text::sprintf('JLIB_INSTALLER_ERROR_CREATE_DIRECTORY', $newdir), Log::WARNING, 'jerror');

						return false;
					}
				}

				$copyFiles[] = $path;
			}

			return $installer->copyFiles($copyFiles, true);
		}

		/**
		 * Method to parse through a layouts element of the installation manifest and remove the files that were installed.
		 *
		 * @param   SimpleXMLElement  $element  The XML node to process.
		 *
		 * @return  boolean  True on success.
		 *
		 * @since  1.0.0
		 */
		protected function removeLayouts(SimpleXMLElement $element)
		{
			if (!$element || !count($element->children())) return false;

			// Get the array of file nodes to process
			$files = $element->children();

			// Get source
			$folder = ((string) $element->attributes()->destination) ? '/' . $element->attributes()->destination : null;
			$source = Path::clean(JPATH_ROOT . '/layouts' . $folder);

			// Process each file in the $files array (children of $tagName).
			foreach ($files as $file)
			{
				$path = Path::clean($source . '/' . $file);

				// Actually delete the files/folders
				if (is_dir($path)) $val = Folder::delete($path);
				else $val = File::delete($path);

				if ($val === false)
				{
					Log::add('Failed to delete ' . $path, Log::WARNING, 'jerror');

					return false;
				}
			}

			if (!empty($folder)) Folder::delete($source);

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

			$installer = $parent->getParent();
			if ($type !== 'uninstall')
			{
				// Parse layouts
				$this->parseLayouts($parent->getParent()->getManifest()->layouts, $parent->getParent());
				// Copy external files
				$this->copyExternalFiles($installer);
			} else {
				// Remove layouts
				$this->removeLayouts($installer->getManifest()->layouts);

				// Remove external files
				$this->removeExternalFiles();
                return true;
			}

			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->select('extension_id')
				->from($db->qn('#__extensions'))
				->where($db->qn('type') . ' = ' . $db->q('plugin'))
				->where($db->qn('folder') . ' = ' . $db->q('system'))
				->where($db->qn('element') . ' = ' . $db->q('eclabs'));
			$pluginId = $db->setQuery($query)->loadResult();

			// Включаем плагин
			$query->clear()
				->update($db->qn('#__extensions'))
				->set($db->qn('enabled').'= 1')
				->where($db->qn('extension_id') . ' = ' . $db->q($pluginId));
			$db->setQuery($query)->execute();

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

            <h3><?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_TITLE");?></h3>
            <ul class="version-history">
                <li><span class="version-upgraded">2.0.0</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_2_0_0");?></li>
                <li><span class="version-fixed">1.0.8</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_8");?></li>
                <li><span class="version-fixed">1.0.7</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_7");?></li>
                <li><span class="version-fixed">1.0.6</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_6");?></li>
                <li><span class="version-fixed">1.0.5</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_5");?></li>
                <li><span class="version-fixed">1.0.4</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_4");?></li>
                <li><span class="version-fixed">1.0.3</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_3");?></li>
                <li><span class="version-upgraded">1.0.2</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_2");?></li>
                <li><span class="version-fixed">1.0.1</span> <?php echo Text::_("PLG_SYSTEM_ECLABS_CHANGE_LOG_1_0_1");?></li>
                <li><span class="version-new">1.0.0</span> First version.</li>
            </ul>
			<?php if ($pluginId) { ?>
            <a class="btn btn-primary btn-large"
               href="<?php echo Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId); ?>"><?php echo Text::_("PLG_SYSTEM_ECLABS_START_USING");?></a>
		<?php } ?>
			<?php if (0): ?>

            <a class="btn" href="#" target="_blank">Read the documentation</a>
            <a class="btn" href="#" target="_blank">Get Support!</a>
		<?php endif; ?>
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
				$app->enqueueMessage(Text::_('PLG_SYSTEM_ECLABS_ERROR_COMPATIBLE_JOOMLA_3'), 'error');
				return false;
			}

			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->select('extension_id')
				->from($db->qn('#__extensions'))
				->where($db->qn('type') . ' = ' . $db->q('library'))
				->where($db->qn('element') . ' = ' . $db->q('eclabs'));

			if (is_null($db->setQuery($query)->loadResult()))
			{
				$app->enqueueMessage(Text::_('PLG_SYSTEM_ECLABS_ERROR_COMPATIBLE_ECLABS'), 'error');
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
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->select($db->quoteName('manifest_cache'))
				->from($db->qn('#__extensions'))
				->where($db->quoteName('type') . ' = ' . $db->quote($info['type']))
				->where($db->quoteName('element') . ' = ' . $db->quote($info['element']));
			$params = json_decode($db->setQuery($query)->loadResult() ?? "", true);
			//var_dump($params);
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
			//$results = $app->triggerEvent('onInstallerBeforeInstallation', array(static::$parent			$dispatcher = Factory::getContainer()->get(DispatcherInterface::class);

            $dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
            $beforeInstallationEvent = new BeforeInstallationEvent(
	            'onInstallerBeforeInstallation',
	            [static::$parent, &$package]
            );
			$results = $dispatcher->dispatch('onInstallerBeforeInstallation', $beforeInstallationEvent)->getArgument('result', []);


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

			$config = Factory::getApplication()->getConfig();
			$tmp_dest = $config->get('tmp_path');

			// Unpack the downloaded package file.
			$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

			// This event allows a custom installation of the package or a customization of the package:
			//$results = $app->triggerEvent('onInstallerBeforeInstaller', array(static::$parent, &$package));
			$beforeInstallationEvent = new BeforeInstallerEvent(
				'onInstallerBeforeInstallation',
				[static::$parent, &$package]
			);
			$results = $dispatcher->dispatch('onInstallerBeforeInstallation', $beforeInstallationEvent)->getArgument('result', []);


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
			//$app->triggerEvent('onInstallerAfterInstaller', array(self::$parent, &$package, $installer, &$result, &$msg));
			$afterInstallerEvent = new AfterInstallerEvent(
				'onInstallerAfterInstaller',
				[self::$parent, &$package, $installer, &$result, &$msg]
			);

			$dispatcher->dispatch('onInstallerAfterInstaller', $afterInstallerEvent);
			$app->enqueueMessage($msg, $msgType);

			// Cleanup the install files.
			if (!is_file($package['packagefile'])) {
				$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
			}

			JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			return $result;
		}

		public function install($parent): void
		{
			self::runSQL("install.sql");
		}

		private static function runSQL($file)
		{
			$db = Factory::getContainer()->get(DatabaseInterface::class);
			$sqlfile = __DIR__ . "/sql/mysql/" . $file;
			if (file_exists($sqlfile)) {
				$buffer = file_get_contents($sqlfile);
				if ($buffer !== false) {
					if (is_callable(array($db, 'splitSql'))) {
						$queries = $db->splitSql($buffer);
					} else {
						$queries = DatabaseDriver::splitSql($buffer);
					}

					foreach ($queries as $query) {
						$query = trim($query);
						if ($query != '' && $query[0] != '#') {
							$db->setQuery($query);
							if (!$db->execute()) {
								JError::raiseWarning(1, Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
							}
						}
					}
				}
			}
		}

		public function uninstall($parent): void
		{
			self::runSQL("uninstall.sql");
		}
	}
}
