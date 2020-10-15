<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Variant;
use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\WarehouseHistoryItem;
use MoySklad\Entities\Documents\Movements\Loss;
use SchGroup\MyWarehouse\Contracts\StockChanger;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

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
     * @var VariantLinker
     */
    private $variantLinker;
    /**
     * StockChanger constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     */
    public function __construct(MoySklad $client, StoreDataKeeper $storeDataKeeper)
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @param WarehouseHistory $warehouseHistory
     */
    public function createBy(WarehouseHistory $warehouseHistory): void
    {
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $lossPositions = $this->buildLossPositions($warehouseHistory);
        $loss = new Loss($this->client);
        $loss->buildCreation()
            ->addOrganization($organization)
            ->addStore($store)
            ->addPositionList($lossPositions)
            ->execute();
    }

    /**
     * @param WarehouseHistory $warehouseHistory
     * @return array
     * @throws \Throwable
     */
    private function buildLossPositions(WarehouseHistory $warehouseHistory): EntityList
    {
        $lossPositions = [];
        $warehouseHistory->items->each(function (WarehouseHistoryItem $historyItem) use (&$lossPositions) {
            $uuid = $historyItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_old - $historyItem->quantity_new;
            $remoteVariant->price = $this->variantLinker->defineBuyPrice($historyItem->variant)['value'];
            $remoteVariant->reason = $historyItem->comment ?? "";
            $lossPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $lossPositions);
    }
}