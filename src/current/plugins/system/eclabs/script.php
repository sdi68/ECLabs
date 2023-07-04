<?php defined('_JEXEC') or die;

/**
 * @package         Econsult Labs Library
 * @subpackage   Econsult Labs system plugin
 * @version           1.0.5
 * @author            ECL <info@econsultlab.ru>
 * @link                 https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Version;

if (!class_exists('plgSystemECLabsInstallerScript'))
{
	/**
	 * Class ECLabsSystemPluginInstallerScript
	 * @since 1.0.0
	 */
	class plgSystemECLabsInstallerScript
	{
		public function preflight($type, $parent)
		{
			if ($type == 'uninstall')
			{
				return true;
			}

			$app = Factory::getApplication();

			$jversion = new Version();
			if (!$jversion->isCompatible('3.0.0'))
			{
				$app->enqueueMessage('Please upgrade to at least Joomla! 3.x before continuing!', 'error');

				return false;
			}

			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('extension_id')
				->from($db->qn('#__extensions'))
				->where($db->qn('type') . ' = ' . $db->q('library'))
				->where($db->qn('element') . ' = ' . $db->q('eclabs'));

			if (is_null($db->setQuery($query)->loadResult()))
			{
				$app->enqueueMessage('The plugin requires an installed library ECLabs to work! Install please this before!', 'error');

				return false;
			}

			return true;
		}

		public function postflight($type, $parent)
		{
			if ($type == 'uninstall')
			{
				return true;
			}

			$db    = Factory::getDbo();
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

			$jversion = new Version;

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

            <h3>the System ECLabs Plugin v1.0.3 Changelog</h3>
            <ul class="version-history">
                <li><span class="version-fixed">1.0.5</span> Bug fix.</li>
                <li><span class="version-fixed">1.0.4</span> Bug fix.</li>
                <li><span class="version-fixed">1.0.3</span> Code optimization.</li>
                <li><span class="version-upgraded">1.0.2</span> Add only token extension authorisation.</li>
                <li><span class="version-fixed">1.0.1</span> Bug fix.</li>
                <li><span class="version-new">NEW</span> First version.</li>
            </ul>
			<?php if ($pluginId) { ?>
            <a class="btn btn-primary btn-large"
               href="<?php echo Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId); ?>">Start
                using the System ECLabs Plugin.</a>
		<?php } ?>
			<?php if (0): ?>

            <a class="btn" href="#" target="_blank">Read the documentation</a>
            <a class="btn" href="#" target="_blank">Get Support!</a>
		<?php endif; ?>
            <div style="clear: both;"></div>
			<?php
		}

		public function install($parent)
		{
			$source = $parent->getParent()->getPath('source');
			$this->runSQL($source, "install.sql");
		}

		protected function runSQL($source, $file)
		{
			$db     = Factory::getDbo();
			$driver = strtolower($db->name);
			if (strpos($driver, 'mysql') !== false)
			{
				$driver = 'mysql';
			}
            elseif ($driver == 'sqlsrv')
			{
				$driver = 'sqlazure';
			}

			//$sqlfile = $source . '/sql/' . $driver . '/' . $file;
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
								JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
							}
						}
					}
				}
			}
		}

		public function uninstall($parent)
		{
			$source = $parent->getParent()->getPath('source');
			$this->runSQL($source, "uninstall.sql");
		}
	}
}
