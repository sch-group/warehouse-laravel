<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use MoySklad\Entities\Documents\Movements\Supply;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders\PositionsFactory;

class IncomingSupplyCreator implements StockChanger
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
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    public function createBy($warehouseHistory, string $remoteChangeName): void
    {
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $counterAgent = $this->storeDataKeeper->defineDummyCounterAgent();
        $supply = new Supply($this->client, ['name' => $remoteChangeName]);
        $positionsBuilder = PositionsFactory::defineIncomingPositionsBuilder($warehouseHistory);
        $supplyPositions = $positionsBuilder->buildPositionsBy($warehouseHistory);
        $supply->buildCreation()
            ->addStore($store)
            ->addOrganization($organization)
            ->addCounterparty($counterAgent)
            ->addPositionList($supplyPositions)
            ->execute();
    }
}