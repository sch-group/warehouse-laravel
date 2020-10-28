<?php


namespace SchGroup\MyWarehouse\Loggers;


class EntitySynchronizeLogger extends MyWarehouseLogger
{
    const LOG_NAME = 'Order_created';

    const ERROR_PATH = 'logs/mywarehouse/entity_synchronization_error.log';

    const ACCESS_PATH = 'logs/mywarehouse/entity_synchronization_access.log';
}