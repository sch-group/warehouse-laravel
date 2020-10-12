<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;


use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use SchGroup\MyWarehouse\Synchonizers\Helpers\DemandsHandlers\ShipmentDemandManager;

class OrderModifier
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
     * OrderModifier constructor.
     * @param MoySklad $client
     */
    public function __construct(
        MoySklad $client,
        StoreDataKeeper $storeDataKeeper,
        OrderPositionsBuilder $orderPositionsBuilder
    ){
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->orderPositionsBuilder = $orderPositionsBuilder;
    }

    /**
     * @param Order $order
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \Throwable
     */
    public function updateOrderInMyWarehouse(Order $order):void
    {
        $remoteOrder = CustomerOrder::query($this->client)->byId($order->getUuid());
        $remoteStatuses = $this->storeDataKeeper->defineOrderStateListKeyedByUuid();
        $newRemoteOrderStatus = $remoteStatuses[$order->status->getUuid()];
        $orderPositions = $this->orderPositionsBuilder->collectOrderPositions($order);
        $remoteOrder->buildUpdate()
            ->addState($newRemoteOrderStatus)
            ->addPositionList($orderPositions)
            ->execute();
        (new ShipmentDemandManager($order, $remoteOrder))->manage();

    }
}