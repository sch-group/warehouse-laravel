<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Product;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistoryItem;

class FindingBonusPositions implements PositionBuilder
{
    /**
     * @var MoySklad
     */
    private $client;

    /**
     * VariantSupplyPositionsBuilder constructor.
     * @param MoySklad $client
     */
    public function __construct(MoySklad $client)
    {
        $this->client = $client;
    }

    /**
     * @param \App\Models\Warehouse\Bonus\WarehouseBonusHistory|\App\Models\Warehouse\WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    function buildPositionsBy($warehouseHistory): EntityList
    {
        $enterPositions = [];
        $warehouseHistory->items->each(function (WarehouseBonusHistoryItem $historyItem) use (&$enterPositions) {
            $uuid = $historyItem->bonus->getUuid();
            $remoteVariant = Product::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteVariant->price = 0;
            $enterPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $enterPositions);
    }
}