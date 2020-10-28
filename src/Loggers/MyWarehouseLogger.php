<?php

namespace SchGroup\MyWarehouse\Loggers;


use Monolog\Logger;

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
        $logger = $this->getLogger(static::LOG_NAME, static::ACCESS_PATH, Logger::ERROR);

        if ($logger && $this->needToLog()) {
            $logger->addInfo($message);
        }
    }

    /**
     * @param string $message
     */
    public function error(string $message): void
    {
        $logger = $this->getLogger(static::LOG_NAME, static::ERROR_PATH);

        if ($logger && $this->needToLog()) {
            $logger->addError($message);
        }
    }

    /**
     * @param string $name
     * @param string $path
     * @param int|string $level
     * @return Logger|null
     */
    private function getLogger(string $name, string $path, string $level = Logger::INFO): ?Logger
    {
        try {
            $logger = new Logger($name);
            $logger->pushHandler(new \Monolog\Handler\StreamHandler(storage_path($path),  $level));

            return $logger;
        } catch (\Exception $exception) {
            return null;
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