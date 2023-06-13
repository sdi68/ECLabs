<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.12
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library;

defined('_JEXEC') or die;
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

use Exception;
use JLog;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;

require_once JPATH_LIBRARIES . '/eclabs/classes/autoload.php';

try
{
	ECLAutoLoader::registerStub('SubscriberInterface', JPATH_LIBRARIES . '/vendor/joomla/event/src/SubscriberInterface.php');
}
catch (Exception $e)
{
	JLog::add($e->getMessage(),JLog::ERROR,"ECLPlugin");
}

/**
 * Абстрактный класс плагина
 * @package     ECLabs\Library
 *
 * @since       1.0.0
 */
abstract class ECLPlugin extends CMSPlugin implements SubscriberInterface
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

		if (File::exists($override))
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

    /**
     * Получает Id плагина
     *
     * @return mixed|null
     *
     * @since 1.0.1
     */
    protected final function _getId(){
        $folder = str_replace(DS.$this->_name,'',str_replace(JPATH_PLUGINS.DS,'',$this->_plugin_path));
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('extension_id'))
            ->from($db->qn('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($folder))
            ->where($db->quoteName('element') . ' = ' . $db->quote($this->_name));
        return $db->setQuery($query)->loadResult();
    }

	/**
	 * Запускает обработчик события по имени для Joomla 4
	 *
	 * @param   string  $fn_name    Имя функции обработчика
	 * @param   Event   $event  Событие
	 *
	 * @return mixed|true
	 *
	 * @since 1.0.10
	 */
	protected final function _runEventHandler(string $fn_name, Event $event) {
		$args = $event->getArguments();
		if(is_array($args))
		{
			if(isset($args['result']))
				unset($args['result']);

			return call_user_func_array(array($this, $fn_name), $args);
		}
		return true;
	}
}