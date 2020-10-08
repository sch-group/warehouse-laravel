<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


abstract class AbstractEntitySynchronizer
{
    /**
     *
     */
    public function synchronize(): void
    {
        $this->applyExistedUuidsToOurEntity();

        $this->addOurEntityToRemoteWarehouse();
    }

    abstract protected function applyExistedUuidsToOurEntity(): void;

    abstract protected function addOurEntityToRemoteWarehouse(): void;

}