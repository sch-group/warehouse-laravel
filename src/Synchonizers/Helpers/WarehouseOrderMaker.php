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

class WarehouseOrderMaker
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
     * WarehouseOrderMaker constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     */
    public function __construct(MoySklad $client, StoreDataKeeper $storeDataKeeper)
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
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
        $remoteOrderStates = $this->storeDataKeeper->defineOrderStateListKeyedByUuid();
        $createdOrderList = (new EntityList($this->client, [$remoteOrder]))
            ->each(function (CustomerOrder $remoteOrder) use ($organization, $ourOrder, $remoteOrderStates) {
                $this->addRelationsToRemoteOrder($ourOrder, $remoteOrder, $organization, $remoteOrderStates);
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
        AbstractEntity $organization,
        array $remoteStatuses
    ): CustomerOrder
    {
        $counterParty = $this->createOrFindCounterParty($ourOrder);
        $orderPositions = $this->defineOrderPositions($ourOrder);
        $positionList = new EntityList($this->client, $orderPositions);
        $state = $remoteStatuses[$ourOrder->status->getUuid()];

        $remoteOrder
            ->buildCreation()
            ->addCounterparty($counterParty)
            ->addOrganization($organization)
            ->addState($state)
            ->addPositionList($positionList);

        return $remoteOrder;
    }

    /**
     * @param Order $order
     * @return \MoySklad\Entities\AbstractEntity|Counterparty|\MoySklad\Entities\Documents\AbstractDocument|AbstractEntity
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    private function createOrFindCounterParty(Order $order)
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

    /**
     * @param Order $ourOrder
     * @return array
     * @throws \Throwable
     */
    private function defineOrderPositions(Order $ourOrder): array
    {
        $orderPositions = [];
        $ourOrder->orderItems->each(function (OrderItem $orderItem) use (&$orderPositions) {
            $uuid = $orderItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            if ($remoteVariant) {
                $remoteVariant->quantity = $orderItem->quantity;
                $remoteVariant->reserve = $orderItem->quantity;
                $remoteVariant->price = (round($orderItem->discounted_price / $orderItem->quantity, 2) * 100);
                $orderPositions[] = $remoteVariant;
            }
        });

        return $orderPositions;
    }
}