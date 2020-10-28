<?php


namespace SchGroup\MyWarehouse\Loggers;


class PricesUpdateLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Prices changed';

    const ERROR_PATH = 'logs/mywarehouse/prices_update_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/prices_update_access.log';
}