<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Product;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;
use App\Models\Warehouse\Bonus\WarehouseBonusHistoryItem;

class IncomingBonusPositions implements PositionBuilder
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
     * @param WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    public function buildPositionsBy($warehouseHistory): EntityList
    {
        $supplyPositions = [];
        $warehouseHistory->items->each(function (WarehouseBonusHistoryItem $historyItem) use (&$supplyPositions) {
            $uuid = $historyItem->bonus->getUuid();
            $remoteVariant = Product::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteVariant->price = 0;
            $supplyPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $supplyPositions);
    }
}