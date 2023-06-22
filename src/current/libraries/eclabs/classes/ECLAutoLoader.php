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
	 * Регистрация класса, интерфейса, трайта  с использованием заглушки для Joomla3, если они у нее отсутствует
	 * Заглушки находятся в папке "J4Stubs"
	 *
	 * @param   string  $stub_name  Имя класса "заглушки"
	 * @param   string  $name_space4    Пространство имен Joomla4 класса
	 * @param   string  $type   Тип (class, interface, trait)
	 * @param   string  $patch_j4  Путь к пространству имен Joomla4 (корень)
	 *
	 *
	 * @throws Exception
	 * @since 1.0.12
	 */
	public static function registerJoomla3Stub(string $stub_name, string $name_space4, string $type, string $patch_j4)
	{
		$fn_name = "";
		switch ($type) {
			case 'class':
				$fn_name = "class_exists";
				break;
			case 'interface':
				$fn_name = "interface_exists";
				break;
			case 'trait':
				$fn_name = "trait_exists";
				break;
			default:
				throw new Exception(Text::sprintf('ECLABS_ERROR_UNSUPPORTED_STUBS_TYPE', $type));
		}

		if(!$fn_name($stub_name)) {
			if (ECLVersion::getJoomlaVersion() < 4)
			{
				// Регистрируем заглушку для Joomla3
				$patch_j3 = __DIR__.'/J4Stubs/'.str_replace('\\', '/', $name_space4).'/src/' .$stub_name. '.php';
				if (File::exists($patch_j3))
				{
					require_once $patch_j3;
				} else {
					throw new Exception(Text::sprintf('ECLABS_ERROR_CLASS_FILE_NOT_EXIST', $patch_j3));
				}
			} else {
				JLoader::registerNamespace($name_space4,$patch_j4,true,true);
			}
		}
	}
}