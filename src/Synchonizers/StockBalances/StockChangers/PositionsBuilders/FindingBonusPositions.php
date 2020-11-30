<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Product;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistoryItem;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\BonusLinker;

class FindingBonusPositions implements PositionBuilder
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
     * @param \App\Models\Warehouse\Bonus\WarehouseBonusHistory|\App\Models\Warehouse\WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    function buildPositionsBy($warehouseHistory): EntityList
    {
        $enterPositions = [];
        $warehouseHistory->items->each(function (WarehouseBonusHistoryItem $historyItem) use (&$enterPositions) {
            $uuid = $historyItem->bonus->getUuid();
            $remoteBonus = Product::query($this->client)->byId($uuid);
            $remoteBonus->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteBonus->price = $this->bonusLinker->defineBuyPrice($historyItem->bonus)['value'];
            $enterPositions[] = $remoteBonus;
        });

        return new EntityList($this->client, $enterPositions);
    }
}