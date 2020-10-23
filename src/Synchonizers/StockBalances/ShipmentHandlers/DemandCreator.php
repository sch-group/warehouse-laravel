<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\ShipmentHandlers;

use MoySklad\Entities\Products\Product;
use MoySklad\MoySklad;
use MoySklad\Entities\Store;
use MoySklad\Lists\EntityList;
use MoySklad\Entities\Counterparty;
use MoySklad\Entities\Organization;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Documents\Movements\Demand;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use MoySklad\Entities\Documents\Positions\CustomerOrderPosition;

class DemandCreator extends DemandHandler
{
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
        $counterParty = $this->remoteOrder->relations->find(Counterparty::class);
        $organization = $this->remoteOrder->relations->find(Organization::class);
        $store = $this->remoteOrder->relations->find(Store::class);
        $positions = $this->buildPositionsFromRemoteOrder();
        $demand = new Demand($this->client, ['name' => (string)$this->remoteOrder->code]);

        $demand->buildCreation()
            ->addStore($store)
            ->addCounterparty($counterParty)
            ->addOrganization($organization)
            ->addCustomerOrder($this->remoteOrder)
            ->addPositionList($positions)
            ->execute();
    }

    /**
     * @return EntityList
     * @throws \MoySklad\Exceptions\Relations\RelationDoesNotExistException
     * @throws \MoySklad\Exceptions\Relations\RelationIsList
     * @throws \MoySklad\Exceptions\Relations\RelationIsSingle
     * @throws \MoySklad\Exceptions\UnknownEntityException
     * @throws \Throwable
     */
    private function buildPositionsFromRemoteOrder(): EntityList
    {
        $collectedPositions = [];
        $this->remoteOrder->relationListQuery('positions')->getList()
            ->each(function (CustomerOrderPosition $customerOrderPosition) use (&$collectedPositions) {
                $remotePosition = $this->defineRemoteItemOrBonusPosition($customerOrderPosition)->fresh();
                $remotePosition->quantity = $customerOrderPosition->quantity;
                $remotePosition->price = $customerOrderPosition->price;
                $collectedPositions[] = $remotePosition;
            });

        return new EntityList($this->client, $collectedPositions);
    }

    /**
     * @param CustomerOrderPosition $customerOrderPosition
     * @return \MoySklad\Entities\AbstractEntity
     * @throws \MoySklad\Exceptions\Relations\RelationDoesNotExistException
     * @throws \MoySklad\Exceptions\Relations\RelationIsList
     */
    private function defineRemoteItemOrBonusPosition(CustomerOrderPosition $customerOrderPosition): \MoySklad\Entities\AbstractEntity
    {
        return $customerOrderPosition->relations->find(Variant::class) ??
            $customerOrderPosition->relations->find(Product::class);
    }
}