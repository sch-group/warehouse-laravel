<?php


namespace SchGroup\MyWarehouse\Loggers;


class IncomingLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Incoming';

    const ERROR_PATH = 'logs/mywarehouse/incoming_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/incoming_access.log';
}