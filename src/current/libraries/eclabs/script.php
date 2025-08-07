<?php \defined('_JEXEC') or die;

/**
 * @package        Econsult Labs Library
 * @version          __DEPLOYMENT_VERSION__
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2025 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\Path;
use Joomla\DI\Container;

return new class () implements ServiceProviderInterface {
	public function register(Container $container): void
	{
		$container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
			/**
			 * The application object
			 *
			 * @var  AdministratorApplication
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected AdministratorApplication $app;

			/**
			 * The Database object.
			 *
			 * @var   DatabaseDriver
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected DatabaseDriver $db;

			/**
			 * Constructor.
			 *
			 * @param   AdministratorApplication  $app  The application object.
			 *
			 * @since __DEPLOY_VERSION__
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
			 * @since   __DEPLOY_VERSION__
			 */
			public function install(InstallerAdapter $adapter): bool
			{
				$installer = $adapter->getParent();
				$this->copyMedia($installer);
				// Parse layouts
				$this->parseLayouts($installer->getManifest()->layouts, $installer);

				return true;
			}

			/**
			 * Function called after the extension is updated.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function update(InstallerAdapter $adapter): bool
			{
				$installer = $adapter->getParent();
				$this->copyMedia($installer);
				// Parse layouts
				$this->parseLayouts($installer->getManifest()->layouts, $installer);

				return true;
			}

			/**
			 * Function called after the extension is uninstalled.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function uninstall(InstallerAdapter $adapter): bool
			{
				//$this->deleteMedia();
                $this->removeLayouts();
				return true;
			}

			/**
			 * Function called before extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function preflight(string $type, InstallerAdapter $adapter): bool
			{
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
			 * @since   __DEPLOY_VERSION__
			 */
			public function postflight(string $type, InstallerAdapter $adapter): bool
			{

				if ($type === 'install' || $type === 'update')
				{
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

                    <h3><?php echo Text::_("LIB_ECLABS_CHANGE_LOG_TITLE");?></h3>
                    <ul class="version-history">
                        <li><span class="version-upgraded">2.0.0</span> <?php echo Text::_("LIB_ECLABS_CHANGE_LOG_2_0_0");?></li>
                    </ul>
					<?php
				}
                return true;
			}

			protected function copyMedia($installer)
			{
				$element = $installer->getManifest()->media;
				$dest    = JPATH_ROOT . '/media/eclabs';
				$folder = (string) $element->attributes()->folder;
				$path = ($folder && file_exists($installer->getPath('source') . '/' . $folder)) ?
					$installer->getPath('source') . '/' . $folder : $installer->getPath('source');
				$folders = Folder::folders($path);

				$copyFiles = [];

				if (!file_exists($dest))
				{
					Folder::create($dest);
				}

				foreach ($folders as $folder)
				{
					$path_current = $path . '/' . $folder;
					if (file_exists($path_current))
					{
						$copyFiles[] = [
							'src'  => $path_current,
							'dest' => $dest . '/' . $folder,
							'type' => 'folder'
						];
					}
				}

				return $installer->copyFiles($copyFiles, true);
			}


			protected function deleteMedia(): bool
			{
				$dest = JPATH_ROOT . '/media/eclabs';



				if (file_exists($dest))
				{
					try
					{
						Folder::delete($dest);
					}
					catch (Exception $e)
					{
						Log::add(Text::sprintf('Ошибка удаления Медиа', $e->getMessage()), Log::WARNING, 'jerror');
						return false;
					}
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
			 * @param   null|SimpleXMLElement  $element  The XML node to process.
			 *
			 * @return  boolean  True on success.
			 *
			 * @since  1.0.0
			 */
			protected function removeLayouts()
			{
				$dest = JPATH_ROOT . '/layouts/libraries/eclabs';

				if (file_exists($dest))
				{
					try
					{
						return Folder::delete($dest);
					}
					catch (Exception $e)
					{
						Log::add(Text::sprintf('Ошибка удаления Лайоут', $e->getMessage()), Log::WARNING, 'jerror');
						return false;
					}
				}

				return true;
			}
		});
	}
};