<?php


namespace SchGroup\MyWarehouse\Loggers;


class MovementsLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Stock Movements';

    const ERROR_PATH = 'logs/mywarehouse/movements_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/movements_access.log';
}