<?php defined('_JEXEC') or die;

/**
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

if (!class_exists('libECLabsInstallerScript'))
{
	/**
	 * Class ECLabsInstallerScript
	 * @since 1.0.0
	 */
	class libECLabsInstallerScript
	{

		public function postflight($type, $parent)
		{

			if ($type === 'install' || $type === 'update')
			{
				$this->copyMedia($parent->getParent());
			}

			if ($type === 'uninstall')
			{
				$this->deleteMedia();
			}

			return true;
		}


		protected function copyMedia($installer)
		{
			$dest    = JPATH_ROOT . '/media/eclabs';
			$path    = Path::clean(JPATH_ROOT . '/libraries/eclabs/fields');
			$folders = Folder::folders($path);

			$copyFiles = [];

			if (!file_exists($dest))
			{
				Folder::create($dest);
			}

			foreach ($folders as $folder)
			{
				$path_current = $path . '/' . $folder . '/media';
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


		protected function deleteMedia()
		{
			$dest = JPATH_ROOT . '/media/eclabs';

			if (file_exists($dest))
			{
				try
				{
					return Folder::delete($dest);
				}
				catch (Exception $e)
				{
					return false;
				}
			}

			return true;
		}
	}
}
