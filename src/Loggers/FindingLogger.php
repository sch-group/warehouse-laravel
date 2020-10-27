<?php


namespace SchGroup\MyWarehouse\Loggers;


class FindingLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Finding/Inventory';

    const ERROR_PATH = 'logs/mywarehouse/finding_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/finding_access.log';
}