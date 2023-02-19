<?php
/**
 * @package         Econsult Labs Library
 * @version         1.0.0
 *
 * @author          ECL <info@econsultlab.ru>
 * @link            https://econsultlab.ru
 * @copyright       Copyright © 2023 ECL All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

use JConfig;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

/**
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
class ECLTools
{

	/**
	 * Build parameter string with unescaped unicode chars to save to DB.
	 * Resolve, for example, save customfields_params with safe_json_encode
	 * in /administrator/components/com_virtuemart/helpers/vmjsapi.php
	 * for example:
	 * param_name = "{$param_value}"|
	 *
	 * @param   string  $param_name   Parameter name
	 * @param   mixed   $param_value  Parameter value
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function buildParamString(string $param_name, $param_value): string
	{
		$str = $param_name . '=';
		$str .= json_encode($param_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$str .= '|';

		return $str;
	}

    /**
     * Сохранить лог в файл.
     *
     * @param string $name Имя файла
     *
     * @param array $data Данные для сохранения в лог
     *
     * @param bool $enabled Разрешить логирование
     *
     * @since   1.0.0
     *
     */
    public static function Storelog(string $name, array $data, bool $enabled = false): void
    {
        if (!$enabled)
            return;

        // Add timestamp to the entry
        $entry = Factory::getDate()->format('[Y-m-d H:i:s]') . ' - ' . json_encode($data) . "\n";

        // Compute the log file's path.
        static $path;
        if (!$path) {
            $config = new JConfig();
            $path = $config->log_path . '/' . $name . '.php';
            if (!file_exists($path)) {
                file_put_contents($path, "<?php die('Forbidden.'); ?>\n\n");
            }
        }
        file_put_contents($path, $entry, FILE_APPEND);
    }

}