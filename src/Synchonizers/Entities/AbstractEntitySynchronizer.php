<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use SchGroup\MyWarehouse\Loggers\EntitySynchronizeLogger;

abstract class AbstractEntitySynchronizer
{
    /**
     *
     */
    public function synchronize(): void
    {
        try {
            $this->applyExistedUuidsToOurEntity();

            $this->addOurEntityToRemoteWarehouse();

        } catch (\Exception $exc) {
            /** @var EntitySynchronizeLogger $logger */
            $logger = app(EntitySynchronizeLogger::class);
            $logger->error($exc->getCode() . " " . $exc->getMessage() . $exc->getTraceAsString() );
        }
    }

    abstract protected function applyExistedUuidsToOurEntity(): void;

    abstract protected function addOurEntityToRemoteWarehouse(): void;

}