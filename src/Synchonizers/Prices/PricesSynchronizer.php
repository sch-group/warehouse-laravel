<?php

namespace SchGroup\MyWarehouse\Synchonizers\Prices;


abstract class PricesSynchronizer
{
    abstract public function syncPrices(): void;
}