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

class IncomingVariantPositions implements PositionBuilder
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
        $supplyPositions = [];
        $warehouseHistory->items->each(function (WarehouseHistoryItem $historyItem) use (&$supplyPositions) {
            $uuid = $historyItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteVariant->price = ($historyItem->purchase_price / $remoteVariant->quantity) * 100;
            $remoteVariant->vat = $this->variantLinker->defineVatRate($historyItem->variant);
            $supplyPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $supplyPositions);
    }
}