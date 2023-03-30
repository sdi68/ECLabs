<?php
/**
 * @package        Econsult Labs Library
 * @version          1.0.1
 * @author           ECL <info@econsultlab.ru>
 * @link                https://econsultlab.ru
 * @copyright      Copyright © 2023 ECL All Rights Reserved
 * @license           http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

namespace ECLabs\Library\Override;

/**
 * Набор параметров для переопределения классов компонента магазина
 * @package     com_receipts
 *
 * @since 1.0.5
 */
class OverrideParams
{
    /**
     * Массив параметров
     * @var array
     * @since 1.0.5
     */
    private $_params = array();

    /**
     * @since 1.0.5
     */
    public function __construct()
    {
        $this->_params = array();
    }

    /**
     * Добавляет новый параметр
     *
     * @param OverrideParam $param  Параметр
     *
     * @return int  Ключ добавленного элемента
     *
     * @since 1.0.5
     */
    public function append(OverrideParam $param): int
    {
        $this->_params[] = $param;
        return count($this->_params) -1;
    }

    /**
     * Удаляет параметр по ключу
     *
     * @param int $key  Ключ элемента массива
     *
     * @return bool true - удаление успешно, false - ключ не найден
     *
     * @since 1.0.5
     */
    public function delete(int $key): bool
    {
        if(array_key_exists($key,$this->_params)) {
            unset($this->_params[$key]);
            return true;
        }
        return false;
    }

    /**
     * @param int $key  Ключ параметра
     *
     * @return OverrideParam|false
     *
     * @since version
     */
    public function getParam(int $key) {
        if(array_key_exists($key,$this->_params)) {
            return $this->_params[$key];
        }
        return false;
    }

}