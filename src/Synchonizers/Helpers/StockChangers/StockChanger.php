<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;

abstract class StockChanger
{

    /**
     * @var MoySklad
     */
    protected $client;
    /**
     * @var StoreDataKeeper
     */
    protected $storeDataKeeper;

    /**
     * StockChanger constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     */
    public function __construct(MoySklad $client, StoreDataKeeper $storeDataKeeper)
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
    }

    abstract public function createBy(WarehouseHistory $warehouseHistory): void;
}