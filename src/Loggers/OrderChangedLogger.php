<?php

namespace SchGroup\MyWarehouse\Loggers;

class OrderChangedLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Order_changed';

    const ERROR_PATH = 'logs/mywarehouse/order_changed_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/order_changed_access.log';
}