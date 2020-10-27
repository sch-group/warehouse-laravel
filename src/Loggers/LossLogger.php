<?php


namespace SchGroup\MyWarehouse\Loggers;


class LossLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Loss';

    const ERROR_PATH = 'logs/mywarehouse/loss_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/loss_access.log';
}