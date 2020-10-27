<?php


namespace SchGroup\MyWarehouse\Loggers;


class CreateOrderLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Order_created';

    const ERROR_PATH = 'logs/mywarehouse/order_create_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/order_created_access.log';
}