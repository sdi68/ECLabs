<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.0
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Override;


/**
 * Параметр переопределения класса компонента магазина
 * @package     com_receipts
 *
 * @since 1.0.5
 */
class OverrideParam
{
    /**
     * Полный путь к файлу переопределяемого класса
     * @var string
     * @since 1.0.5
     */
    private $_overrided_file = "";

    /**
     * Имя переопределяемого класса
     * @var string
     * @since 1.0.5
     */
    private $_overrided_class = "";

    /**
     * Полный путь к файлу расширяющего класса
     * @var string
     * @since 1.0.5
     */
    private $_override_file = "";

    /**
     * @param string $_overrided_file
     * @param string $_overrided_class
     * @param string $_override_file
     * @since 1.0.5
     */
    public function __construct(string $_overrided_file, string $_overrided_class, string $_override_file)
    {
        $this->_overrided_file = $_overrided_file;
        $this->_overrided_class = $_overrided_class;
        $this->_override_file = $_override_file;
    }


    /**
     * Возвращает полный путь к файлу переопределяемого класса
     * @return string
     * @since 1.0.5
     */
    public function getOverridedFile(): string
    {
        return $this->_overrided_file;
    }

    /**
     * Возвращает имя переопределяемого класса
     * @return string
     * @since 1.0.5
     */
    public function getOverridedClass(): string
    {
        return $this->_overrided_class;
    }

    /**
     * Возвращает полный путь к файлу расширяющего класса
     * @return string
     * @since 1.0.5
     */
    public function getOverrideFile(): string
    {
        return $this->_override_file;
    }

}