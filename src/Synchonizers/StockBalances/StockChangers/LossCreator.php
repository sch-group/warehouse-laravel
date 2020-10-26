<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use MoySklad\Entities\Documents\Movements\Loss;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders\PositionsFactory;

class LossCreator implements StockChanger
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
     * StockChanger constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     */
    public function __construct(MoySklad $client, StoreDataKeeper $storeDataKeeper)
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
    }

    /**
     * @param \App\Models\Warehouse\Bonus\WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @param string $remoteChangeName
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \Throwable
     */
    public function createBy($warehouseHistory, string $remoteChangeName): void
    {
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $positionsBuilder = PositionsFactory::defineLossPositionsBuilder($warehouseHistory);
        $lossPositions = $positionsBuilder->buildPositionsBy($warehouseHistory);

        $loss = new Loss($this->client, ['name' => $remoteChangeName]);
        $loss->buildCreation()
            ->addOrganization($organization)
            ->addStore($store)
            ->addPositionList($lossPositions)
            ->execute();
    }

}