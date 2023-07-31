<?php
/**
 * @package     ECLabs\Library
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace ECLabs\Library\Traits;

use ECLabs\Library\ECLVersion;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\Event\Event;

trait ECLPlugin
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
     * @param int $jVersion
     * @since 1.0.0
     */
    private function _setJVersion(): void
    {
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
     * @return Event|true
     *
     * @since 1.0.10
     */
    protected final function _runEventHandler(string $fn_name, Event $event) {
        $args = $event->getArguments();
        if(is_array($args))
        {
            if(isset($args['result']))
                unset($args['result']);

            //return call_user_func_array(array($this, $fn_name), $args);
            $ret = call_user_func_array(array($this, $fn_name), $args);
            if(!$event->hasArgument('result')) {
                $event->addArgument('result',array());
            }
            $result = $event->getArgument('result',null);
            if(is_array($result))
            {
                $result[] = $ret;
                $event->setArgument('result', $result);

                return $event;
            }

        }
        return true;
    }

    /**
     * Подписка на события для Joomla 4
     *
     * @return string[]
     *
     * @since 1.0.12
     */
    public abstract static function getSubscribedEvents(): array;


}