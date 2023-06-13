<?php
/**
 * @package             Econsult Labs Library
 * @version             1.0.12
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2023 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use Exception;
use JLoader;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;

/**
 * Namespace classes loader
 * @package     ECLabs\Library
 *
 * @since       1.0.7
 */
class ECLAutoLoader
{
	/**
	 * Пакетная загрузка пространств имен
	 *
	 * @param   array  $params  Параметры пространств имен. Массив каждый элемент содержит массив из
	 *                          classNameSpace - Наименование пространства имен
	 *                          classRootPath - Путь к папке с классами пространства имен
	 *
	 *
	 * @since 1.0.7
	 */
	public static function autoloadBatch(array $params): void
	{
		foreach ($params as $param)
		{
			if (isset($param['classNameSpace']) && $param['classNameSpace'] &&
				isset($param['classRootPath']) && $param['classRootPath']
			)
			{
				self::autoload($param['classNameSpace'], $param['classRootPath']);
			}
		}
	}

	/**
	 * Инициализация загрузчика классов
	 *
	 * @param   string  $classNameSpace  Наименование пространства имен
	 * @param   string  $classRootPath   Путь к папке с классами пространства имен
	 *
	 *
	 * @since 1.0.7
	 */
	public static function autoload(string $classNameSpace, string $classRootPath = ""): void
	{
		$_classNameSpace = $classNameSpace;
		$_classRootPath  = $classRootPath ?? dirname(__FILE__);
		spl_autoload_register(function ($className) use ($_classRootPath, $_classNameSpace) {
			$length = strlen($_classNameSpace);
			if (strncmp($_classNameSpace, $className, $length) === 0)
			{
				$path = $_classRootPath;
			}
			else
			{
				return;
			}
			$path .= str_replace('\\', '/', substr($className, $length)) . '.php';

			if (file_exists($path))
			{
				require $path;
			}
		});
	}

	/**
	 * Регистрация класса с использованием заглушки для Joomla3, если класс у нее отсутствует
	 * Заглушки находятся в папке "J4Stubs"
	 *
	 * @param   string  $className  Наименование класса
	 * @param   string  $patch_j4   Путь к файлу класса в Joomla 4
	 *
	 *
	 * @throws Exception
	 * @since 1.0.12
	 */
	public static function registerStub(string $className, string $patch_j4)
	{
		if (!class_exists($className))
		{
			if (ECLVersion::getJoomlaVersion() < 4)
			{
				// Регистрируем заглушку для Joomla3 (интерфейс не используется)
				$path = JPATH_LIBRARIES . '/eclabs/classes/J4Stubs/' . $className . '.php';
				if (File::exists($path))
				{
					require_once $path;
				}
				else
				{
					throw new Exception(Text::sprintf('ECLABS_ERROR_CLASS_FILE_NOT_EXIST', $path));
				}
			}
			else
			{
				// Регистрируем интерфейс для Joomla 4 (вместо use)
				JLoader::register($className, $patch_j4, true);
			}
		}
	}
}