<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Product;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistoryItem;

class LossBonusPositions implements PositionBuilder
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
    public function buildPositionsBy($warehouseHistory): EntityList
    {
        $lossPositions = [];
        $warehouseHistory->items->each(function (WarehouseBonusHistoryItem $historyItem) use (&$lossPositions) {
            $uuid = $historyItem->bonus->getUuid();
            $remoteVariant = Product::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_old - $historyItem->quantity_new;
            $remoteVariant->price = 0;
            $remoteVariant->reason = $historyItem->comment ?? "";
            $lossPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $lossPositions);
    }
}