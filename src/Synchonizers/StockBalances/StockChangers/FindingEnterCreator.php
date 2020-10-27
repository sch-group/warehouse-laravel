<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;


use MoySklad\MoySklad;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Loggers\FindingLogger;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;
use SchGroup\MyWarehouse\Synchonizers\Helpers\EnterMaker;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders\PositionsFactory;

/**
 * Создает оприходавание при инвентаризации
 * Class FindingEnterCreator
 * @package SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers
 */
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
     * @var FindingLogger
     */
    private $logger;

    /**
     * StockChanger constructor.
     * @param MoySklad $client
     * @param FindingLogger $logger
     * @param StoreDataKeeper $storeDataKeeper
     * @param EnterMaker $enterMaker
     */
    public function __construct(
        MoySklad $client,
        FindingLogger $logger,
        StoreDataKeeper $storeDataKeeper,
        EnterMaker $enterMaker)
    {
        $this->logger = $logger;
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
        $this->logger->info("Find create started for {$remoteChangeName} {$warehouseHistory->id}");
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $positionsBuilder = PositionsFactory::defineInventoryPositionsBuilder($warehouseHistory);
        $enterPositions = $positionsBuilder->buildPositionsBy($warehouseHistory);
        $this->logger->info("Positions for {$remoteChangeName} {$warehouseHistory->id} " . $enterPositions->toJson(0));
        $enter = $this->enterMaker->addNewEnter($organization, $store, $enterPositions, $remoteChangeName);
        $this->logger->info("New enter added {$enter->name}");
    }

}