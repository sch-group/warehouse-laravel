<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;


use MoySklad\MoySklad;
use App\Models\Orders\Order;
use SchGroup\MyWarehouse\Loggers\OrderChangedLogger;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\ShipmentHandlers\ShipmentDemandManager;

class OrderModifier
{
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var OrderChangedLogger
     */
    private $logger;
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
     * @param OrderChangedLogger $logger
     * @param StoreDataKeeper $storeDataKeeper
     * @param OrderPositionsBuilder $orderPositionsBuilder
     */
    public function __construct(
        MoySklad $client,
        OrderChangedLogger $logger,
        StoreDataKeeper $storeDataKeeper,
        OrderPositionsBuilder $orderPositionsBuilder
    ){
        $this->client = $client;
        $this->logger = $logger;
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
        $this->logger->info("Order {$order->order_number} has started to update");
        $remoteOrder = CustomerOrder::query($this->client)->byId($order->getUuid());
        $remoteStatuses = $this->storeDataKeeper->defineOrderStateListKeyedByUuid();
        $newRemoteOrderStatus = $remoteStatuses[$order->status->getUuid()];
        $orderPositions = $this->orderPositionsBuilder->collectOrderPositions($order);
        $this->logger->info("Order {$order->order_number} positions to update:" . $orderPositions->toJson(0));
        $remoteOrder->buildUpdate()
            ->addState($newRemoteOrderStatus)
            ->addPositionList($orderPositions)
            ->execute();
        $this->logger->info("Order {$order->order_number} has updated");
        (new ShipmentDemandManager($order, $remoteOrder))->manage();

    }
}