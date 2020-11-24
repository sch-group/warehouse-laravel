<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;

use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Lists\EntityList;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\Counterparty;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use SchGroup\MyWarehouse\Loggers\CreateOrderLogger;

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
     * @var CreateOrderLogger
     */
    private $logger;

    /**
     * WarehouseOrderMaker constructor.
     * @param MoySklad $client
     * @param CreateOrderLogger $logger
     * @param StoreDataKeeper $storeDataKeeper
     * @param OrderPositionsBuilder $orderPositionsBuilder
     */
    public function __construct(
        MoySklad $client,
        CreateOrderLogger $logger,
        StoreDataKeeper $storeDataKeeper,
        OrderPositionsBuilder $orderPositionsBuilder
    )
    {
        $this->logger = $logger;
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
        $this->logger->info("Order create begins with id: {$ourOrder->order_number}");
        $createdOrderList = (new EntityList($this->client, [$remoteOrder]))
            ->each(function (CustomerOrder $remoteOrder) use ($organization, $store, $ourOrder, $remoteOrderStates) {
                $this->addRelationsToRemoteOrder($ourOrder, $remoteOrder, $store, $organization, $remoteOrderStates);
            })->massCreate();

        $uuid = $createdOrderList[0]->id;
        $ourOrder->saveMyWareHouseEntity($uuid, $ourOrder->id);
        $this->logger->info("Order has been created with uuid: {$uuid}");
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
        $this->logger->info("Order positions of {$ourOrder->order_number} : " . $orderPositions->toJson(0));
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