<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Variant;
use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\WarehouseHistoryItem;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

class FindingVariantPositions  implements PositionBuilder
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
     * @param \App\Models\Warehouse\Bonus\WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    function buildPositionsBy($warehouseHistory): EntityList
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