<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;

use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Lists\EntityList;
use App\Models\Orders\OrderItem;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\Counterparty;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Entities\Products\Variant;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Documents\Orders\CustomerOrder;

class OrderMaker
{
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var StoreDataKeeper
     */
    private $storeDataKeeper;
    /**
     * @var OrderPositionsBuilder
     */
    private $orderPositionsBuilder;

    /**
     * WarehouseOrderMaker constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     * @param OrderPositionsBuilder $orderPositionsBuilder
     */
    public function __construct(
        MoySklad $client,
        StoreDataKeeper $storeDataKeeper,
        OrderPositionsBuilder $orderPositionsBuilder
    )
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->orderPositionsBuilder = $orderPositionsBuilder;
    }

    /**
     * @param Order $ourOrder
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    public function createSingleOrder(Order $ourOrder): void
    {
        $remoteOrder = $this->createInstanceOfRemoteOrder($ourOrder);
        $organization = $this->storeDataKeeper->defineOrganization();
        $store = $this->storeDataKeeper->defineStore();
        $remoteOrderStates = $this->storeDataKeeper->defineOrderStateListKeyedByUuid();
        $createdOrderList = (new EntityList($this->client, [$remoteOrder]))
            ->each(function (CustomerOrder $remoteOrder) use ($organization, $store, $ourOrder, $remoteOrderStates) {
                $this->addRelationsToRemoteOrder($ourOrder, $remoteOrder, $store, $organization, $remoteOrderStates);
            })->massCreate();

        $uuid = $createdOrderList[0]->id;
        $ourOrder->saveMyWareHouseEntity($uuid, $ourOrder->id);
    }

    /**
     * @param Order $order
     * @return CustomerOrder
     */
    public function createInstanceOfRemoteOrder(Order $order): CustomerOrder
    {
        return new CustomerOrder($this->client, [
            "name" => (string)$order->order_number,
            "code" => (string)$order->id,
        ]);
    }

    /**
     * @param Order $ourOrder
     * @param CustomerOrder $remoteOrder
     * @param AbstractEntity $organization
     * @param array $remoteStatuses
     * @return CustomerOrder
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    public function addRelationsToRemoteOrder(
        Order $ourOrder,
        CustomerOrder $remoteOrder,
        AbstractEntity $store,
        AbstractEntity $organization,
        array $remoteStatuses
    ): CustomerOrder
    {
        $counterParty = $this->createOrFindCounterAgent($ourOrder);
        $orderPositions = $this->orderPositionsBuilder->collectOrderPositions($ourOrder);
        $state = $remoteStatuses[$ourOrder->status->getUuid()];

        $remoteOrder
            ->buildCreation()
            ->addCounterparty($counterParty)
            ->addOrganization($organization)
            ->addStore($store)
            ->addState($state)
            ->addPositionList($orderPositions);

        return $remoteOrder;
    }

    /**
     * @param Order $order
     * @return Counterparty|AbstractEntity
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    private function createOrFindCounterAgent(Order $order): Counterparty
    {
        $counterParty = Counterparty::query($this->client, QuerySpecs::create(["maxResults" => 1]))
            ->filter((new FilterQuery())->eq("code", (string)$order->order_number));

        if (!empty($counterParty[0])) {
            return $counterParty[0];
        }

        return (new Counterparty($this->client, [
            'name' => (string)$order->order_number,
            'code' => (string)$order->id,
            'email' => $order->email,
            'phone' => $order->phone,
        ]))->create();
    }
}