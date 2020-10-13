<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers\StockChangers;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Products\Variant;
use App\Models\Warehouse\WarehouseHistory;
use App\Models\Warehouse\WarehouseHistoryItem;
use MoySklad\Entities\Documents\Movements\Supply;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;

class SupplyCreator extends StockChanger
{
    /**
     * @var MoySklad
     */
    protected $client;
    /**
     * @var StoreDataKeeper
     */
    protected $storeDataKeeper;


    /**
     * @param WarehouseHistory $warehouseHistory
     */
    public function createBy(WarehouseHistory $warehouseHistory): void
    {
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $counterAgent = $this->storeDataKeeper->defineDummyCounterAgent();
        $supply = new Supply($this->client, ['name' => (string)$warehouseHistory->id]);
        $supplyPositions = $this->defineSupplyPositions($warehouseHistory);
        $supply->buildCreation()
            ->addStore($store)
            ->addOrganization($organization)
            ->addCounterparty($counterAgent)
            ->addPositionList($supplyPositions)
            ->execute();
    }

    /**
     * @param WarehouseHistory $warehouseHistory
     * @return EntityList
     * @throws \Throwable
     */
    private function defineSupplyPositions(WarehouseHistory $warehouseHistory): EntityList
    {
        $supplyPositions = [];
        $warehouseHistory->items->each(function (WarehouseHistoryItem $historyItem) use (&$supplyPositions) {
            $uuid = $historyItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            $remoteVariant->quantity = $historyItem->quantity_new - $historyItem->quantity_old;
            $remoteVariant->price = ($historyItem->purchase_price / $remoteVariant->quantity) * 100;
            $supplyPositions[] = $remoteVariant;
        });

        return new EntityList($this->client, $supplyPositions);
    }
}