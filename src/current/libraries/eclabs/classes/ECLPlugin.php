<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.0
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

defined('_JEXEC') or die;

use Exception;
use JFile;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

/**
 * Абстрактный класс плагина
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
abstract class ECLPlugin extends CMSPlugin
{
	/**
	 * Путь до файла плагина коннектора
	 * Должна быть инициализирована в самом коннекторе
	 * @var string
	 * @since 1.0.0
	 */
	protected $_plugin_path = "";

	/**
	 * Флаг разрешения логирования работы плагина
	 * @var bool
	 * @since 1.0.0
	 */
	protected $enabled_log = false;

    /**
     * Версия Joomla
     * @var int
     * @since 1.0.0
     */
    protected $jVersion = 3;

	/**
	 * @param $subject
	 * @param $config
	 *
	 * @since 1.0.0
	 */

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

        $this->jVersion = ECLVersion::getJoomlaVersion();
	}


	/**
	 * Подключаем медиа конкретного плагина
	 * @return void
	 * @since 1.0.0
	 */
	abstract protected function _addMedia(): void;

	/**
	 * Build Layout path
	 *
	 * @param   string  $layout  Layout name
	 *
	 * @return   string  Layout Path
	 * @throws Exception
	 * @since   1.0.0
	 *
	 */
	protected final function _buildLayoutPath(string $layout): string
	{
		$app = Factory::getApplication();

		$core_file = $this->_plugin_path . '/' . $this->_name . '/tmpl/' . $layout . '/'.$layout.'.php';
		$override  = JPATH_BASE . '/' . 'templates' . '/' . $app->getTemplate() . '/html/plugins/' .
			$this->_type . '/' . $this->_name . '/' . $layout .'/'.$layout.'.php';

		if (JFile::exists($override))
		{
			return $override;
		}
		else
		{
			return $core_file;
		}
	}

	/**
	 * Builds the layout to be shown, along with hidden fields.
	 *
	 * @param   object  $vars    Data from component
	 * @param   string  $layout  Layout name
	 *
	 * @return   string  Layout html
	 * @throws Exception
	 * @since   1.0.0
	 *
	 */
	protected final function _buildLayout(object $vars, string $layout = 'default'): string
	{
		// Load the layout & push variables
		ob_start();
		$layout = $this->_buildLayoutPath($layout);
		$this->_addMedia();
		// Подключаем шаблон
		include $layout;
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}


	/**
	 * Логирование работы плагина
	 *
	 * @param   array  $data
	 *
	 * @return void
	 * @since 1.0.0
	 */
	protected final function _logging(array $data): void
	{
		ECLTools::Storelog($this->_name, $data, $this->enabled_log);
	}
}