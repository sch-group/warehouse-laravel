<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers\StockChangers;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Variant;
use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\WarehouseHistoryItem;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use SchGroup\MyWarehouse\Synchonizers\Helpers\EnterMaker;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

class StockEnterCreator implements StockChanger
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
     * @var VariantLinker
     */
    private $variantLinker;

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
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @param WarehouseHistory $warehouseHistory
     * @throws \Throwable
     */
    public function createBy(WarehouseHistory $warehouseHistory): void
    {
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $enterPositions = $this->buildEntersPositionsBy($warehouseHistory);
        $enterName = $warehouseHistory->id . "_" . $warehouseHistory->action;
        $this->enterMaker->addNewEnter($organization, $store, $enterPositions, $enterName);
    }

    /**
     * @param WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    private function buildEntersPositionsBy(WarehouseHistory $warehouseHistory): EntityList
    {
        $enterPositions = [];
        $warehouseHistory->items->each(function (WarehouseHistoryItem $historyItem) use (&$enterPositions) {
            $uuid = $historyItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteVariant->price = $this->variantLinker->defineBuyPrice($historyItem->variant)['value'];
            $enterPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $enterPositions);
    }
}