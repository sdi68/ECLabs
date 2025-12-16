<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Event\Installer\AfterInstallerEvent;
use Joomla\CMS\Event\Installer\BeforeInstallationEvent;
use Joomla\CMS\Event\Installer\BeforeInstallerEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Installer\Administrator\Model\InstallModel;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Installer\Manifest\PackageManifest;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Registry\Registry;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
            /**
             * The application object
             *
             * @var  AdministratorApplication
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            protected AdministratorApplication $app;

            /**
             * The Database object.
             *
             * @var   DatabaseDriver
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            protected DatabaseDriver $db;

            /**
             * Minimum Joomla version required to install the extension.
             *
             * @var  string
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            protected string $minimumJoomla = '4.2';

            /**
             * Minimum PHP version required to install the extension.
             *
             * @var  string
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            protected string $minimumPhp = '7.4';

            /**
             * Адаптер установщика
             * @var ?InstallerAdapter
             * @since __DEPLOYMENT_VERSION__
             */
            protected ?InstallerAdapter $parent = null;
            /**
             * Текущая версия устанавливаемого элемента
             * @var string
             * @since __DEPLOYMENT_VERSION__
             */
            protected string $current_version = "";

            /**
             * Тип устанавливаемого элемента (компонент, плагин, библиотека и т.п.)
             * @var string
             * @since __DEPLOYMENT_VERSION__
             */
            protected string $type = "";

            /**
             * Наименование устанавливаемого элемента
             * @var string
             * @since __DEPLOYMENT_VERSION__
             */
            protected string $name = "";

            /**
             * Группа устанавливаемого элемента (если плагин)
             * site или administrator если модуль
             * @var string
             * @since __DEPLOYMENT_VERSION__
             */
            protected string $group = "";

            /**
             * Предыдущая версия устанавливаемого элемента
             * @var string
             * @since __DEPLOYMENT_VERSION__
             */
            protected string $previous_version = "";
            /**
             * Зависимости устанавливаемого элемента
             * @var array
             * @since __DEPLOYMENT_VERSION__
             */
            protected array $dependencies = array();

            /**
             * Extension files.
             *
             * @var  array
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            protected array $externalFiles = array();

            /**
             * Список файлов и папок, которые надо удалить при обновлении
             *  array(
             *  "folders" =>array([относительный путь к каталогу],...),
             *  "files" =>array([относительный путь к файлу],...)
             *  );
             * например:
             * array(
             * "folders" => array(),
             * "files" => array("/media/com_receipts/js/diagnostic.js")
             * );
             * @var array
             * @since __DEPLOYMENT_VERSION__
             */
            protected array $to_remove = array();

            /**
             * Относительный путь к скриптам установки базы данных
             * @var string
             * __DEPLOYMENT_VERSION__
             */
            protected string $sql_path = "";

            /**
             * Инициализация параметров скрипта установки
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return void
             * __DEPLOYMENT_VERSION__
             */
            private function _initialize(InstallerAdapter $adapter): void
            {
                $manifest     = $adapter->getParent()->manifest;
                $this->parent = $adapter;
                $this->type   = "package";
                $this->name   = trim((string) $manifest->attributes()['name']);
                $this->group  = trim((string) $adapter->getParent()->manifest->attributes()['group']);

                $this->current_version  = trim((string) $manifest->attributes()['version']);
                $this->previous_version = $this->_getPreviousVersion();
                $this->dependencies     = array();

                $this->externalFiles = array();
                $this->to_remove     = array();
                $this->sql_path      = "";
            }

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
             * @since __DEPLOYMENT_VERSION__
             */
            private function _renderHistory(): void
            {
                ?>
                <h3><?php echo Text::_('PKG_ECLABS_CHANGE_LOG_TITLE'); ?></h3>
                <ul class="version-history">
                    <li>
                        <span class="version-upgraded">2.0.1</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_2_0_1'); ?>
                    </li>
                    <li>
                        <span class="version-upgraded">2.0.0</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_2_0_0'); ?>
                    </li>
                    <li>
                        <span class="version-upgraded">1.0.8</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_1_0_8'); ?>
                    </li>
                    <li>
                        <span class="version-upgraded">1.0.7</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_1_0_7'); ?>
                    </li>
                    <li>
                        <span class="version-upgraded">1.0.6</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_1_0_6'); ?>
                    </li>
                    <li><span class="version-fixed">1.0.5</span> <?php echo Text::_('PKG_ECLABS_CHANGE_LOG_1_0_6'); ?>
                    </li>
                </ul>
                <div style="clear: both;"></div>
                <?php
            }

            /**
             * Check all compatibilities requirements for element
             * @return bool true if compatible
             * @throws Exception
             * @since __DEPLOYMENT_VERSION__
             */
            private function _checkCompatible(): bool
            {
                $app = Factory::getApplication();

                // Check joomla version
                if (!(new Version())->isCompatible($this->minimumJoomla))
                {
                    $app->enqueueMessage(Text::sprintf('PKG_ECLABS_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
                            'error');

                    return false;
                }

                // Check PHP
                if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
                {
                    $app->enqueueMessage(Text::sprintf('PKG_ECLABS_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
                            'error');

                    return false;
                }

                return true;
            }

            /**
             * Constructor.
             *
             * @param   AdministratorApplication  $app  The application object.
             *
             * @since __DEPLOYMENT_VERSION__
             */
            public function __construct(AdministratorApplication $app)
            {
                $this->app = $app;
                $this->db  = Factory::getContainer()->get('DatabaseDriver');
            }

            /**
             * Function called after the extension is installed.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   __DEPLOYMENT_VERSION__
             */
            public function install(InstallerAdapter $adapter): bool
            {
                return $this->_runSQL("");
            }

            /**
             * Function called after the extension is updated.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   __DEPLOYMENT_VERSION__
             */
            public function update(InstallerAdapter $adapter): bool
            {
                $this->_removeIts();

                if (!$this->_checkIfUpdate())
                {
                    return $this->_runSQL("");
                }

                return true;
            }

            /**
             * Function called after the extension is uninstalled.
             *
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   __DEPLOYMENT_VERSION__
             */
            public function uninstall(InstallerAdapter $adapter): bool
            {
                return $this->_runSQL("");
            }

            /**
             * Function called before extension installation/update/removal procedure commences.
             *
             * @param   string            $type     The type of change (install or discover_install, update, uninstall)
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   __DEPLOYMENT_VERSION__
             */
            public function preflight(string $type, InstallerAdapter $adapter): bool
            {
                if (!in_array($type, ['install', 'update']))
                {
                    return true;
                }

                // Check compatible
                if (!$this->_checkCompatible())
                {
                    return false;
                }

                $this->_initialize($adapter);

                return true;
            }


            /**
             * Function called after extension installation/update/removal procedure commences.
             *
             * @param   string            $type     The type of change (install or discover_install, update, uninstall)
             * @param   InstallerAdapter  $adapter  The adapter calling this method
             *
             * @return  boolean  True on success
             *
             * @since   __DEPLOYMENT_VERSION__
             */
            public function postflight(string $type, InstallerAdapter $adapter): bool
            {
                $installer = $adapter->getParent();
                if ($type !== 'uninstall')
                {
                    // Parse layouts
                    $this->_parseLayouts($installer->getManifest()->layouts, $installer);
                    // Copy external files
                    if (count($this->externalFiles))
                    {
                        self::_copyExternalFiles($installer);
                    }

                    if (count($this->dependencies))
                    {
                        $this->_installDependencies();
                    }

                }
                else
                {
                    // Remove layouts
                    $manifest = $installer->getManifest();
                    if ($manifest)
                    {
                        $layout = $installer->getManifest()->layouts;
                        if ($layout)
                        {
                            $this->_removeLayouts($layout);
                        }
                    }
                    // Remove external files
                    if (count($this->externalFiles))
                    {
                        self::_removeExternalFiles();
                    }

                    return true;
                }

                if ($this->type === 'plugin' && $this->group)
                {
                    $this->_enablePlugin($adapter);
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
                $this->_renderHistory();
                ?>
                <div style="clear: both;"></div>
                <?php
                return true;
            }

            /**
             * Enable plugin after installation.
             *
             * @param   InstallerAdapter  $adapter  Parent object calling object.
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _enablePlugin(InstallerAdapter $adapter)
            {
                // Prepare plugin object
                $plugin          = new \stdClass();
                $plugin->type    = 'plugin';
                $plugin->element = $adapter->getElement();
                $plugin->folder  = (string) $adapter->getParent()->manifest->attributes()['group'];
                $plugin->enabled = 1;

                // Update record
                $this->db->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);
            }

            /**
             * Get previous version for element
             * @return string
             * @since __DEPLOYMENT_VERSION__
             */
            private function _getPreviousVersion(): string
            {

                $xml_file = $this->_getXmlFile();

                if (!$xml_file)
                {
                    return '';
                }

                $manifest = new PackageManifest($xml_file);

                return property_exists($manifest, 'version') ? trim($manifest->version) : '';
            }

            /**
             * Get path for manifest file
             * @return string
             * @since __DEPLOYMENT_VERSION__
             */
            private function _getXmlFile(): string
            {

                switch ($this->type)
                {
                    case "plugin":
                        $xml_file = JPATH_PLUGINS . '/' . $this->group . '/' . $this->name . '/' . $this->name . '.xml';
                        break;
                    case "component":
                        $xml_file = JPATH_ADMINISTRATOR . '/components/com_' . $this->name . '/' . $this->name . '.xml';
                        break;
                    case "library":
                        $xml_file = JPATH_LIBRARIES . '/' . $this->name . '.xml';
                        break;
                    case "package":
                        $xml_file = JPATH_MANIFESTS . '/packages/pkg_' . $this->name . '.xml';
                        break;
                    case "module":
                        $p = "";
                        switch ($this->group)
                        {
                            case "site":
                                $p = JPATH_ROOT;
                                break;
                            case "administrator":
                                $p = JPATH_ADMINISTRATOR;
                                break;
                        }
                        $xml_file = $p . '/modules/' . $this->name . '.xml';
                        break;
                    default:
                        $xml_file = "";
                }

                if ($xml_file && file_exists($xml_file))
                {
                    return $xml_file;
                }

                return '';
            }

            /**
             * Запускает скрипты sql из файла
             *
             * @param   string  $file  Имя файла со скриптами
             *
             * @return bool
             *
             * @throws Exception
             * @since __DEPLOYMENT_VERSION__
             */
            private function _runSQL(string $file): bool
            {
                if (empty($file))
                    return true;
                $db = Factory::getContainer()->get('DatabaseDriver');
                // Сформировать путь относительно расположения в проекте
                $sqlfile = __DIR__ . $this->sql_path . $file;
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
                            $queries = Installer::splitSql($buffer);
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
             * Удаляет файлы или каталоги. Например, если в новой версии они не нужны.
             *
             * array(
             * "folders" =>array([относительный путь к каталогу],...),
             * "files" =>array([относительный путь к файлу],...)
             *
             * @return void
             *
             * @throws Exception
             * @since __DEPLOYMENT_VERSION__
             */
            private function _removeIts(): void
            {
                $ret = true;
                foreach ($this->to_remove as $type => $items)
                {
                    switch ($type)
                    {
                        case "folders":
                            if (count($items))
                            {
                                foreach ($items as $item)
                                {
                                    $path = JPATH_ROOT . $item;
                                    if (file_exists($path))
                                    {
                                        try
                                        {
                                            Folder::delete($path);
                                        }
                                        catch (Exception $e)
                                        {
                                            $ret = false;
                                            Factory::getApplication()->enqueueMessage(Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $path), "warning");
                                        }
                                    }
                                }
                            }
                            break;
                        case "files":
                            if (count($items))
                            {
                                foreach ($items as $item)
                                {
                                    $path = JPATH_ROOT . $item;
                                    if (File::exists($path))
                                    {
                                        try
                                        {
                                            File::delete($path);
                                        }
                                        catch (Exception $e)
                                        {
                                            $ret = false;
                                            Factory::getApplication()->enqueueMessage(Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $path), "warning");
                                        }
                                    }
                                }
                            }
                            break;
                        default:
                    }
                }
            }

            /**
             * Проверяет необходимо ли отдельные действия при обновлении
             *
             * @return bool
             *
             * @since __DEPLOYMENT_VERSION__
             */
            private function _checkIfUpdate(): bool
            {
                return false;
            }

            /**
             * Method to parse through a layout element of the installation manifest and take appropriate action.
             *
             * @param   SimpleXMLElement  $element    The XML node to process.
             * @param   Installer         $installer  Installer calling object.
             *
             * @return  boolean  True on success.
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _parseLayouts(SimpleXMLElement $element, Installer $installer): bool
            {
                if (!$element || !count($element->children()))
                    return false;

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
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _removeLayouts(SimpleXMLElement $element): bool
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
             * Method to delete external files.
             *
             * @return  bool  True on success.
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _removeExternalFiles(): bool
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
             * Method to copy external files.
             *
             * @param   Installer  $installer  Installer calling object.
             *
             * @return  bool True on success, False on failure.
             *
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _copyExternalFiles(Installer $installer): bool
            {
                $copyFiles = [];
                foreach ($this->externalFiles as $path)
                {
                    if ($path['src'] && $path['dest'])
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
             * Install all dependencies for this element
             * @throws Exception
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _installDependencies(): bool
            {

                if (count($this->dependencies))
                {
                    foreach ($this->dependencies as $dependency)
                    {
                        $info = $this->_getDependencyInfo($dependency);
                        if ($info)
                        {
                            if ($this->_needDependencyInstall($info))
                            {
                                return $this->_installDependency($info['downloads']['downloadurl']);
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
             * @since  __DEPLOYMENT_VERSION__
             */
            private function _getDependencyInfo(array $dependency): array|false
            {
                $app        = Factory::getApplication();
                $version    = new Version;
                $httpOption = new Registry;
                $httpOption->set('userAgent', $version->getUserAgent('Joomla', true, false));

                // JHttp transport throws an exception when there's no response.
                try
                {
                    $http     = (new Joomla\Http\HttpFactory)->getHttp($httpOption);
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
                /** @var  SimpleXMLElement $item */
                foreach ($xml->update as $item)
                {
                    $tp          = $item->children()->targetPlatform;
                    $dep_version = (string) ($tp->attributes()->version);

                    if (str_contains($dep_version, $version_mask))
                    {
                        return json_decode(json_encode($item), true);
                    }
                }

                return false;
            }

            /**
             * Проверяет возможность и необходимость установки расширения
             *
             * @param   array  $info  Параметры устанавливаемого расширения
             *
             * @return bool
             *
             * @since __DEPLOYMENT_VERSION__
             */
            private function _needDependencyInstall(array $info): bool
            {
                // Получаем информацию об установленном расширении
                $db    = Factory::getContainer()->get('DatabaseDriver');
                $query = $db->getQuery(true);
                $query->select($db->quoteName('manifest_cache'))
                        ->from($db->qn('#__extensions'))
                        ->where($db->quoteName('type') . ' = ' . $db->quote($info['type']))
                        ->where($db->quoteName('element') . ' = ' . $db->quote($info['element']));
                $params = json_decode($db->setQuery($query)->loadResult() ?? "", true);

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
             * @since __DEPLOYMENT_VERSION__
             */
            private function _installDependency(string $url): bool
            {
                $app        = Factory::getApplication();
                $dispatcher = Factory::getContainer()->get('dispatcher');
                // Load installer plugins for assistance if required:
                PluginHelper::importPlugin('installer', null, true, $dispatcher);

                $package = null;

                // This event allows an input pre-treatment, a custom pre-packing or custom installation.
                // (e.g. from a JSON description).

                // TODO В Извлечь метод Joomla 5 d события требуется передать модель, а не адаптер установщика
                /** @var  InstallModel $model */
                $installModel = Factory::getApplication()->bootComponent('com_installer')->getMVCFactory()->createModel("Install", "Administrator", ['ignore_request' => true]);

                $eventBefore = new BeforeInstallationEvent('onInstallerBeforeInstallation', [
                        'subject' => $installModel/*self::$parent*/,
                        'package' => &$package, // @todo: Remove reference in Joomla 6, see InstallerEvent::__constructor()
                ]);

                $results = $dispatcher->dispatch('onInstallerBeforeInstallation', $eventBefore)->getArgument('result', []);


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
                $eventBeforeInst = new BeforeInstallerEvent('onInstallerBeforeInstaller', [
                        'subject' => $installModel/*self::$parent*/,
                        'package' => &$package, // @todo: Remove reference in Joomla 6, see InstallerEvent::__constructor()
                ]);
                $results         = $dispatcher->dispatch('onInstallerBeforeInstaller', $eventBeforeInst)->getArgument('result', []);

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
                $installer = Installer::getInstance();

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

                $eventAfterInst = new AfterInstallerEvent('onInstallerAfterInstaller', [
                        'subject'         => $installModel/*self::$parent*/,
                        'package'         => &$package, // @todo: Remove reference in Joomla 6, see InstallerEvent::__constructor()
                        'installer'       => $installer,
                        'installerResult' => &$result, // @todo: Remove reference in Joomla 6, see AfterInstallerEvent::__constructor()
                        'message'         => &$msg, // @todo: Remove reference in Joomla 6, see AfterInstallerEvent::__constructor()
                ]);
                $dispatcher->dispatch('onInstallerAfterInstaller', $eventAfterInst);
                $package = $eventAfterInst->getPackage();
                $result  = $eventAfterInst->getInstallerResult();
                $msg     = $eventAfterInst->getMessage();

                // Set some model state values.
                $app->enqueueMessage($msg, $msgType);
                $app->setUserState('com_installer.message', $installer->message);
                $app->setUserState('com_installer.extension_message', $installer->get('extension_message'));
                $app->setUserState('com_installer.redirect_url', $installer->get('redirect_url'));

                // Cleanup the install files.
                if (!is_file($package['packagefile']))
                {
                    $package['packagefile'] = $app->get('tmp_path') . '/' . $package['packagefile'];
                }

                JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

                return $result;
            }
        });
    }
};
