<?php

namespace SchGroup\MyWarehouse\Synchonizers\Helpers\DemandsHandlers;

use MoySklad\Entities\Counterparty;
use MoySklad\Entities\Documents\Movements\Demand;
use MoySklad\Entities\Documents\Positions\CustomerOrderPosition;
use MoySklad\Entities\Organization;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Store;
use MoySklad\Lists\EntityList;
use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use SchGroup\MyWarehouse\Synchonizers\Helpers\OrderPositionsBuilder;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;

class DemandCreator extends DemandHandler
{
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var CustomerOrder
     */
    protected $remoteOrder;

    /**
     * @var MoySklad
     */
    protected $client;

    /**
     *
     */
    public function handle(): void
    {
        /** @var OrderPositionsBuilder $itemsBuilder */
        $itemsBuilder = app(OrderPositionsBuilder::class);
       $counterParty = $this->remoteOrder->relations->find(Counterparty::class);
       $organization = $this->remoteOrder->relations->find(Organization::class);
       $store =  $this->remoteOrder->relations->find(Store::class);
       $positions = $this->buildPositionsFromRemoteOrder();
//       $positions = $itemsBuilder->collectOrderPositions($this->order);
//       dd($positions);
       $demand = new Demand($this->client, ['name' => (string)$this->order->order_number]);
       $demand->buildCreation()
           ->addStore($store)
           ->addCounterparty($counterParty)
           ->addOrganization($organization)
           ->addCustomerOrder($this->remoteOrder)
           ->addPositionList($positions)
           ->execute();
       dd($demand);
    }

    private function buildPositionsFromRemoteOrder(): EntityList
    {
        $collectedPositions = [];
        $this->remoteOrder->relationListQuery('positions')->getList()
            ->each(function (CustomerOrderPosition $customerOrderPosition) use (&$collectedPositions) {
                $remoteVariant = $customerOrderPosition->relations->find(Variant::class)->fresh();
                $remoteVariant->quantity = $customerOrderPosition->quantity;
                $remoteVariant->price = $customerOrderPosition->price;
                $remoteVariant->reserve = $remoteVariant->quantity;
                $remoteVariant->overhead = $remoteVariant->quantity;
//                dd($remoteVariant)
                $collectedPositions[] = $remoteVariant;
            });
        return new EntityList($this->client, $collectedPositions);
    }
}