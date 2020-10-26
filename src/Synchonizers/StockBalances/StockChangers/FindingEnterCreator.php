<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;
use SchGroup\MyWarehouse\Synchonizers\Helpers\EnterMaker;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders\PositionsFactory;

class FindingEnterCreator implements StockChanger
{
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var StoreDataKeeper
     */
    private $storeDataKeeper;
    /**
     * @var EnterMaker
     */
    private $enterMaker;

    /**
     * StockChanger constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     * @param EnterMaker $enterMaker
     */
    public function __construct(MoySklad $client, StoreDataKeeper $storeDataKeeper, EnterMaker $enterMaker)
    {
        $this->client = $client;
        $this->enterMaker = $enterMaker;
        $this->storeDataKeeper = $storeDataKeeper;
    }

    /**
     * @param WarehouseHistory|WarehouseBonusHistory $warehouseHistory
     * @param string $remoteChangeName
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \Throwable
     */
    public function createBy($warehouseHistory, string $remoteChangeName): void
    {
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $positionsBuilder = PositionsFactory::defineInventoryPositionsBuilder($warehouseHistory);
        $enterPositions = $positionsBuilder->buildPositionsBy($warehouseHistory);
        $this->enterMaker->addNewEnter($organization, $store, $enterPositions, $remoteChangeName);
    }

}