<?php
/**
 * @package             Econsult Labs Library
 * @version             __DEPLOYMENT_VERSION__
 * @author              ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright           Copyright © 2025 ECL All Rights Reserved
 * @license             http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Override;


use Joomla\Filesystem\File;
use Joomla\Filesystem\Stream;

/**
 * Функции переопределения классов компонентов Joomla 3.x
 * @since 1.0.5
 */
class OverrideHelper
{
	/**
	 * Маркер начала переопределяемой области кода класса
	 * @since 1.0.5
	 */
	const BEGIN_OVERRIDE_MARKER = '//~ override BOF';

	/**
	 * Маркер конца переопределяемой области кода класса
	 * @since 1.0.5
	 */
	const END_OVERRIDE_MARKER = '//~ override EOF';

	/**
	 * Маркер старого кода, заменяемого при переопределении переопределяемой области кода класса
	 * @since 1.0.5
	 */
	const COMMENT_OVERRIDE_MARKER = '// ';


	/**
	 * Убирает изменения, сделанные в переопределенном классе
	 *
	 * @param   string  $file  Полный путь к переопределенному файлу
	 *
	 * @since 1.0.5
	 */
	public static function clear_override_classes(string $file)
	{
		if (File::exists($file))
		{
			$start_override = '//~ override BOF';
			$stop_override  = '//~ override EOF';
			$source         = File::read($file);
			$is_block       = false;
			$out            = array();
			foreach (explode("\n", $source) as $str)
			{
				if (strpos($str, $start_override) !== false)
				{
					$is_block = true;
					continue;
				}
				else
				{
					if (strpos($str, $stop_override) !== false)
					{
						$is_block = false;
						continue;
					}
					else if ($is_block)
					{
						if (strpos($str, self::COMMENT_OVERRIDE_MARKER) !== false)
						{
							$out[] = str_replace(self::COMMENT_OVERRIDE_MARKER, '', $str);
						}
						else
						{
							continue;
						}
					}
					else
					{
						$out[] = $str;
					}
				}
			}
			$out = implode("\n", $out);

			File::write($file, $out);
		}
	}

	/**
	 * Изменяет переопределяемый класс.
	 * Изменяет имя класса на [class]Core
	 * Включает расширяющий класс
	 *
	 * @param   OverrideParam  $override_param  Параметры переопределения
	 *
	 * @since 1.0.5
	 */
	public static function override_classes(OverrideParam $override_param)
	{
		$file         = $override_param->getOverridedFile();
		$class        = $override_param->getOverridedClass();
		$file_extends = $override_param->getOverrideFile();

		if (File::exists($file))
		{
			$source = File::read($file);
			if (strpos($source, self::BEGIN_OVERRIDE_MARKER) === false)
			{
				// не было переопределения
				// изменяем название класса (только в определении)
				$o_block = self::BEGIN_OVERRIDE_MARKER . "\n" . self::COMMENT_OVERRIDE_MARKER . "class " . $class . "\nclass " . $class . "Core\n" . self::END_OVERRIDE_MARKER . "\n";
				$source  = str_replace('class ' . $class, $o_block, $source);
				// подключаем расширяющий класс
				$o_block = '<?php' . "\n" . self::BEGIN_OVERRIDE_MARKER . "\n include_once " . $file_extends . ";\n" . self::END_OVERRIDE_MARKER . "\n";
				$source  = str_replace('<?php', $o_block, $source);
				File::write($file, $source);
			}
		}
	}
}
