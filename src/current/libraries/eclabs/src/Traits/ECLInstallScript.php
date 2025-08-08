<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Traits;

\defined('_JEXEC') or die;

use Exception;
use JInstallerHelper;
use Joomla\CMS\Event\Installer\AfterInstallerEvent;
use Joomla\CMS\Event\Installer\BeforeInstallationEvent;
use Joomla\CMS\Event\Installer\BeforeInstallerEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\Manifest\PackageManifest as JPackageManifest;
use SimpleXMLElement;

/**
 * Скрипт инсталлятора расширений
 * @since 2.0.0
 */
trait ECLInstallScript
{
	/**
	 * Адаптер установщика
	 * @var object
	 * @since 2.0.0
	 */
	static $parent = null;
	/**
	 * Текущая версия устанавливаемого элемента
	 * @var string
	 * @since 2.0.0
	 */
	static $current_version = "";
	/**
	 * Наименование устанавливаемого элемента
	 * @var null
	 * @since 2.0.0
	 */
	static $name = "";
	/**
	 * Предыдущая версия устанавливаемого элемента
	 * @var string
	 * @since 2.0.0
	 */
	static $previous_version = "";
	/**
	 * Зависимости устанавливаемого элемента
	 * @var array
	 * @since 2.0.0
	 */
	static $dependencies = array();

	/**
	 * Устанавливает зависимости текущего расширения.
	 * Например:
	 * return array(
	 * array(
	 * 'type' => 'package',  // Тип расширения
	 * 'element' => 'pkg_eclabs', // Название расширения, как оно зафиксировано #__extensions.element
	 * 'url' => "https://econsultlab.ru/component/swjprojects/jupdate?element=pkg_eclabs" // URL сервера обновлений расширения
	 * )
	 * );
	 * @return array
	 *
	 * @since 2.0.0
	 */
	abstract private static function _getDependencies(): array;

	/**
	 * Устанавливает наименование element расширения (как в таблице #__extension)
	 * @return string
	 *
	 * @since 2.0.0
	 */
	abstract private static function _getElement(): string;

	/**
	 * Устанавливает тип расширения (как в таблице #__extension)
	 * @return string
	 *
	 * @since 2.0.0
	 */
	abstract private static function _getElementType(): string;

	/**
	 * Устанавливает каталог плагина (как в таблице #__extension)
	 * @return string
	 *
	 * @since 2.0.0
	 */
	abstract private static function _getElementFolder(): string;

	/**
	 * Формирует историю изменений расширения. Используется html.
	 * Например:
	 * ?>
	 * <h3><?php echo Text::_("PLG_SYSTEM_RECEIPTSLOADER_CHANGE_LOG_TITLE"); ?></h3>
	 * <ul class="version-history">
	 * <li><span class="version-upgraded">1.0.1</span> <?php echo Text::_("PLG_SYSTEM_RECEIPTSLOADER_CHANGE_LOG_1_0_1");?></li>
	 * <li><span class="version-new">1.0.0</span> First version.</li>
	 * </ul>
	 * <?php
	 *
	 * @return void
	 *
	 * @since 2.0.0
	 */
	abstract private static function _renderHistory(): void;

	/**
	 * Check all compatibilities requirements for element
	 * @return bool true if compatible
	 * @throws Exception
	 * @since 2.0.0
	 */
	abstract private static function checkCompatible(): bool;

	/**
	 * Method to check compatible.
	 *
	 * @param   string            $type    Type of PostFlight action.
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @return  boolean  Compatible current version or not.
	 *
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function preflight(string $type, InstallerAdapter $parent): bool
	{

		$manifest = $parent->getManifest();

		if (!in_array($type, ['install', 'update']))
		{
			return true;
		}

		if (!self::checkCompatible())
		{
			return false;
		}

		static::$parent           = $parent;
		static::$name             = trim($manifest->name);
		static::$current_version  = trim($manifest->version);
		static::$previous_version = self::getPreviousVersion();
		static::$dependencies     = self::_getDependencies();

		if (count(static::$dependencies))
		{
			return self::installDependencies();
		}

		return true;
	}

	/**
	 * Runs right after any installation action.
	 *
	 * @param   string            $type    Type of PostFlight action. Possible values are:
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @return  boolean  True on success
	 * @since  2.0.0
	 */
	public function postflight(string $type, InstallerAdapter $parent)
	{

		if ($type == 'uninstall')
		{
			return true;
		}

		$type     = self::_getElementType();
		$folder   = self::_getElementFolder();
		$pluginId = null;
		if ($type === 'plugin' && $folder)
		{
			$db    = Factory::getContainer()->get(DatabaseInterface::class);
			$query = $db->getQuery(true);
			$query->select($db->quoteName('extension_id'))
				->from($db->qn('#__extensions'))
				->where($db->quoteName('element') . ' = ' . $db->quote(self::_getElement()))
				->where($db->quoteName('type') . ' = ' . $db->quote($type))
				->where($db->quoteName('folder') . ' = ' . $db->quote($folder));

			$pluginId = $db->setQuery($query)->loadResult();

			// Включаем плагин
			$query->clear()
				->update($db->qn('#__extensions'))
				->set($db->qn('enabled') . '= 1')
				->where($db->qn('extension_id') . ' = ' . $db->q($pluginId));
			$db->setQuery($query)->execute();
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
		<?php
		self::_renderHistory();
		?>
		<?php if ($pluginId) { ?>
        <a class="btn btn-primary btn-large"
           href="<?php echo Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId); ?>"><?php echo Text::_("ECL_START_USING_PLUGIN"); ?></a>
	<?php } ?>
        <div style="clear: both;"></div>
		<?php
		return true;
	}

	/**
	 * Install all dependencies for this element
	 * @throws Exception
	 * @since  2.0.0
	 */
	private static function installDependencies(): bool
	{

		if (count(static::$dependencies))
		{
			foreach (static::$dependencies as $dependency)
			{
				$info = self::getDependencyInfo($dependency);
				if ($info)
				{
					if (self::needDependencyInstall($info))
					{
						return self::installDependency($info['downloads']['downloadurl']);
					}
				}
			}

		}

		return true;
	}

	/**
	 * Get dependency info from update server
	 *
	 * @param   array  $dependency  Dependency
	 *
	 * @return array | false (if unsuccessful)
	 * @throws Exception
	 * @since  2.0.0
	 */
	private static function getDependencyInfo(array $dependency): array|false
	{
		$app        = Factory::getApplication();
		$version    = new Version;
		$httpOption = new Registry;
		$httpOption->set('userAgent', $version->getUserAgent('Joomla', true, false));

		// JHttp transport throws an exception when there's no response.
		try
		{
			$http     = HttpFactory::getHttp($httpOption);
			$response = $http->get($dependency['url'], array(), 20);
		}
		catch (\RuntimeException $e)
		{
			$response = null;
		}

		if ($response === null || $response->code !== 200)
		{
			$app->enqueueMessage(Text::sprintf('COM_INSTALLER_MSG_ERROR_CANT_CONNECT_TO_UPDATESERVER', $dependency['url']), 'warning');

			return false;
		}
		// Выбираем нужную секцию манифеста обновления в зависимости от версии Joomla
		$version_mask = '3.';
		switch (true)
		{
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
		foreach ($xml->update as $item)
		{
			//var_export($item->targetplatform);
			$tp = $item->targetplatform;
			if (str_contains((string) ($tp->attributes()->version), $version_mask))
			{
				return json_decode(json_encode($item), true);
			}
		}

		return false;
	}

	/**
	 * Get previous version for element
	 * @return string
	 * @since 2.0.0
	 */
	private static function getPreviousVersion(): string
	{

		$xml_file = self::getXmlFile();

		if (!$xml_file)
		{
			return '';
		}

		$manifest = new JPackageManifest($xml_file);

		return isset($manifest->version) ? trim($manifest->version) : '';
	}

	/**
	 * Get path for manifest file
	 * @return string
	 * @since 2.0.0
	 */
	private static function getXmlFile()
	{
		$xml_file = JPATH_MANIFESTS . '/packages/pkg_' . static::$name . '.xml';

		if (file_exists($xml_file))
		{
			return $xml_file;
		}

		$xml_file = JPATH_LIBRARIES . '/' . static::$name . '.xml';

		if (file_exists($xml_file))
		{
			return $xml_file;
		}

		$xml_file = JPATH_ADMINISTRATOR . '/components/com_' . static::$name . '/' . static::$name . '.xml';

		if (file_exists($xml_file))
		{
			return $xml_file;
		}

		return '';
	}

	/**
	 * Проверяет возможность и необходимость установки расширения
	 *
	 * @param   array  $info  Параметры устанавливаемого расширения
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	private static function needDependencyInstall(array $info): bool
	{
		// Получаем информацию об установленном расширении
		$db    = Factory::getContainer()->get(DatabaseInterface::class);
		$query = $db->getQuery(true);
		$query->select($db->quoteName('manifest_cache'))
			->from($db->qn('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote($info['type']))
			->where($db->quoteName('element') . ' = ' . $db->quote($info['element']));
		$params = json_decode($db->setQuery($query)->loadResult() ?? "", true);
		//var_dump($params);
		$ret = false;
		if (is_null($params))
		{
			// Расширение не установлено. Надо устанавливать.
			$ret = true;
		}
		else if (is_array($params))
		{
			// Если на сайте более старая версия, то нужно устанавливать расширение
			$ret = (bool) version_compare($info['version'], $params['version'], '>');
		}

		return $ret;
	}

	/**
	 * Устанавливает расширение по URL
	 *
	 * @param   string  $url  URL сервера обновлений расширения
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 2.0.0
	 */
	private static function installDependency(string $url): bool
	{
		// Load installer plugins for assistance if required:
		PluginHelper::importPlugin('installer');

		$app = Factory::getApplication();

		$package = null;

		// This event allows an input pre-treatment, a custom pre-packing or custom installation.
		// (e.g. from a JSON description).

		$dispatcher              = Factory::getContainer()->get(DispatcherInterface::class);
		$beforeInstallationEvent = new BeforeInstallationEvent(
			'onInstallerBeforeInstallation',
			[static::$parent, &$package]
		);
		$results                 = $dispatcher->dispatch('onInstallerBeforeInstallation', $beforeInstallationEvent)->getArgument('result', []);

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			return false;
		}

		// Download the package at the URL given.
		$p_file = JInstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

			return false;
		}

		$config   = Factory::getApplication()->getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file.
		$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		// This event allows a custom installation of the package or a customization of the package:
		$beforeInstallationEvent = new BeforeInstallerEvent(
			'onInstallerBeforeInstallation',
			[static::$parent, &$package]
		);
		$results                 = $dispatcher->dispatch('onInstallerBeforeInstallation', $beforeInstallationEvent)->getArgument('result', []);

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
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
		if (is_array($package) && isset($package['dir']) && is_dir($package['dir']))
		{
			$installer->setPath('source', $package['dir']);

			if (!$installer->findManifest())
			{
				JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
				$app->enqueueMessage(Text::sprintf('COM_INSTALLER_INSTALL_ERROR', '.'), 'warning');

				return false;
			}
		}

		// Was the package unpacked?
		if (!$package || !$package['type'])
		{
			JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			$app->enqueueMessage(Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

			return false;
		}

		// Install the package.
		if (!$installer->install($package['dir']))
		{
			// There was an error installing the package.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_ERROR',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = false;
			$msgType = 'error';
		}
		else
		{
			// Package installed successfully.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = true;
			$msgType = 'message';
		}

		// This event allows a custom a post-flight:

		$afterInstallerEvent = new AfterInstallerEvent(
			'onInstallerAfterInstaller',
			[self::$parent, &$package, $installer, &$result, &$msg]
		);

		$dispatcher->dispatch('onInstallerAfterInstaller', $afterInstallerEvent);
		$app->enqueueMessage($msg, $msgType);

		// Cleanup the install files.
		if (!is_file($package['packagefile']))
		{
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}

		JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		return $result;
	}

	/**
	 * Вызывается при обновлении
	 *
	 * @param   InstallerAdapter  $parent
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function update(InstallerAdapter $parent): bool
	{
		if (!self::_checkIfUpdate())
		{
			return self::runSQL("install.sql");
		}

		return true;
	}

	/**
	 * Проверяет необходимо ли отдельные действия при обновлении
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	private function _checkIfUpdate(): bool
	{
		return false;
	}

	/**
	 * Вызывается при установке
	 *
	 * @param   InstallerAdapter  $parent
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function install(InstallerAdapter $parent): bool
	{
		return self::runSQL("install.sql");
	}

	/**
	 * Запускает скрипты sql из файла
	 *
	 * @param   string  $file  Имя файла со скриптами
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 2.0.0
	 */
	private static function runSQL(string $file): bool
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);
		// Сформировать путь относительно расположения в проекте
		$sqlfile = __DIR__ . "/sql/mysql/" . $file;
		if (file_exists($sqlfile))
		{
			$buffer = file_get_contents($sqlfile);
			if ($buffer !== false)
			{
				if (is_callable(array($db, 'splitSql')))
				{
					$queries = $db->splitSql($buffer);
				}
				else
				{
					$queries = JInstallerHelper::splitSql($buffer);
				}

				foreach ($queries as $query)
				{
					$query = trim($query);
					if ($query != '' && $query[0] != '#')
					{
						$db->setQuery($query);
						if (!$db->execute())
						{
							Factory::getApplication()->enqueueMessage(Text::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)), "error");

							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Вызывается при удалении
	 *
	 * @param   InstallerAdapter  $parent
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 2.0.0
	 */
	public function uninstall(InstallerAdapter $parent): bool
	{
		return self::runSQL("uninstall.sql");
	}
}