<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Product;
use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;
use App\Models\Warehouse\Bonus\WarehouseBonusHistoryItem;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\BonusLinker;

class IncomingBonusPositions implements PositionBuilder
{
    /**
     * @var MoySklad
     */
    private $client;

    /**
     * @var BonusLinker
     */
    private $bonusLinker;

    /**
     * VariantSupplyPositionsBuilder constructor.
     * @param MoySklad $client
     */
    public function __construct(MoySklad $client)
    {
        $this->client = $client;
        $this->bonusLinker = app(config('my_warehouse.bonus_linker_class'));
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
            $remoteBonus = Product::query($this->client)->byId($uuid);
            $remoteBonus->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteBonus->price = $this->bonusLinker->defineBuyPrice($historyItem->bonus)['value'];
            $supplyPositions[] = $remoteBonus;
        });

        return new EntityList($this->client, $supplyPositions);
    }
}