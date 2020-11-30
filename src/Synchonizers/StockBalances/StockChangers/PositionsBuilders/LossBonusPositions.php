<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Product;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistoryItem;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\BonusLinker;

class LossBonusPositions implements PositionBuilder
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
    public function buildPositionsBy($warehouseHistory): EntityList
    {
        $lossPositions = [];
        $warehouseHistory->items->each(function (WarehouseBonusHistoryItem $historyItem) use (&$lossPositions) {
            $uuid = $historyItem->bonus->getUuid();
            $remoteBonus = Product::query($this->client)->byId($uuid);
            $remoteBonus->quantity = $historyItem->quantity_old - $historyItem->quantity_new;
            $remoteBonus->price = $this->bonusLinker->defineBuyPrice($historyItem->bonus)['value'];
            $remoteBonus->reason = $historyItem->comment ?? "";
            $lossPositions[] = $remoteBonus;
        });

        return new EntityList($this->client, $lossPositions);
    }
}