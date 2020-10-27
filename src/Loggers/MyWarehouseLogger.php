<?php

namespace SchGroup\MyWarehouse\Loggers;


abstract class MyWarehouseLogger
{
    const LOG_NAME = '';

    const ERROR_PATH = '';

    const ACCESS_PATH = '';

    /**
     * @param string $message
     */
    public function info(string $message): void
    {
        $logger = getLogger(static::LOG_NAME, static::ACCESS_PATH);

        if ($logger && $this->needToLog()) {
            $logger->addInfo($message);
        }
    }

    /**
     * @param string $message
     */
    public function error(string $message): void
    {
        $logger = getLogger(static::LOG_NAME, static::ERROR_PATH);

        if ($logger && $this->needToLog()) {
            $logger->addError($message);
        }
    }

    /**
     * @return bool
     */
    private function needToLog(): bool
    {
        return env('MY_WAREHOUSE_LOG') === 'on';
    }
}