<?php

namespace ECLabs\Library\Traits;

use ECLabs\Library\ECLLogging\ECLLogging;

trait ECLLogTrait
{
    protected string $_entity_name="";

    protected bool $_enable_logging;
    /**
     * Логирование работы плагина
     *
     * @param   string  $type     Тип записи
     * @param   string  $message  Сообщение для записи в лог (например имя переменной)
     * @param   array   $data     Переменная для записи в лог
     *
     * @return void
     * @see   ECLLOG
     * @since 1.0.20
     */
    protected final function _addLog(string $type, string $message, mixed $data = null): void
    {
        ECLLogging::add(
            array(
                "source"           => $this->_entity_name,
                "enabled"          => $this->_enable_logging,
                "logger"           => "ECLabs\\Library\\ECLLogging\\Loggers\\ECLabDefaultLogger",
                "back_trace_level" => 3
            ),
            array(
                "timestamp" => "",
                "type"      => $type,
                "caller"    => "",
                "message"   => $message,
                "data"      => $data,
            )
        );
    }

}