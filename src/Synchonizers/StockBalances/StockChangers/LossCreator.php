<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Loggers\LossLogger;
use MoySklad\Entities\Documents\Movements\Loss;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders\PositionsFactory;

/**
 * Создает Списание в моем складе при добвлении потери
 * Class LossCreator
 * @package SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers
 */
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
     * @var LossLogger
     */
    private $logger;

    /**
     * StockChanger constructor.
     * @param MoySklad $client
     * @param LossLogger $logger
     * @param StoreDataKeeper $storeDataKeeper
     */
    public function __construct(MoySklad $client, LossLogger $logger, StoreDataKeeper $storeDataKeeper)
    {
        $this->client = $client;
        $this->logger = $logger;
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
        $this->logger->info("Loss create started for {$remoteChangeName} {$warehouseHistory->id}");
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $positionsBuilder = PositionsFactory::defineLossPositionsBuilder($warehouseHistory);
        $lossPositions = $positionsBuilder->buildPositionsBy($warehouseHistory);
        $this->logger->info("Positions for {$remoteChangeName} {$warehouseHistory->id} " . $lossPositions->toJson(0));
        $loss = new Loss($this->client, ['name' => $remoteChangeName]);
        $loss->buildCreation()
            ->addOrganization($organization)
            ->addStore($store)
            ->addPositionList($lossPositions)
            ->execute();
        $this->logger->info("New supply added {$loss->name}");
    }

}