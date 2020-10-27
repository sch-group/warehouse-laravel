<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\ShipmentHandlers;

use App\Models\Orders\Order;
use App\Models\Orders\Status;
use MoySklad\Entities\Documents\Orders\CustomerOrder;

/**
 * Класс уаправляет отгрузками,
 * отгрузка уменьшает резерв в остатках, когда мы отправили заказ нужно создать отгрузку если отменили заказ
 * то удаляем отгрузку
 * Class DemandManager
 * @package SchGroup\MyWarehouse\Synchonizers\Helpers
 */
class ShipmentDemandManager
{
    /**
     * @return string[]
     */
    public function statusHandlers(): array
    {
        return [
            Status::getDeliveryStatusCode() => DemandCreator::class,
            Status::getCanceledStatusCode() => DemandDestroyer::class,
            Status::getCombinedStatusCode() => DemandDestroyer::class,
        ];
    }
    /**
     * @var Order
     */
    private $order;
    /**
     * @var CustomerOrder
     */
    private $remoteOrder;

    /**
     * StatusHandler constructor.
     * @param Order $order
     * @param CustomerOrder $remoteOrder
     */
    public function __construct(Order $order, CustomerOrder $remoteOrder)
    {
        $this->order = $order;
        $this->remoteOrder = $remoteOrder;
    }

    /**
     *
     */
    public function manage(): void
    {
        $handler = $this->buildHandler();
        if($handler) {
            $handler->handle();
        }
    }

    /**
     * @return DemandHandler
     */
    private function buildHandler(): ?DemandHandler
    {
        if(empty($this->statusHandlers()[$this->order->status->code])) {
            return null;
        }
        $handlerClassName = $this->statusHandlers()[$this->order->status->code];

        return new $handlerClassName($this->remoteOrder);
    }
}