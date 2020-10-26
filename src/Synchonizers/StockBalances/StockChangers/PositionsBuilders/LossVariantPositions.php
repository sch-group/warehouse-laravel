<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Variant;
use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\WarehouseHistoryItem;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

class LossVariantPositions implements PositionBuilder
{
    /**
     * @var MoySklad
     */
    private $client;

    /**
     * @var VariantLinker
     */
    private $variantLinker;

    /**
     * VariantSupplyPositionsBuilder constructor.
     * @param MoySklad $client
     */
    public function __construct(MoySklad $client)
    {
        $this->client = $client;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @param WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    public function buildPositionsBy($warehouseHistory): EntityList
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