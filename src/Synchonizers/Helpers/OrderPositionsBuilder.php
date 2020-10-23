<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;

use MoySklad\Entities\Products\Product;
use MoySklad\MoySklad;
use App\Models\Orders\Order;
use App\Models\Bonuses\Bonus;
use App\Models\Orders\Status;
use MoySklad\Lists\EntityList;
use App\Models\Orders\OrderItem;
use MoySklad\Entities\Products\Variant;

class OrderPositionsBuilder
{
    /**
     * @var MoySklad
     */
    private $client;

    public function __construct(MoySklad $client)
    {
        $this->client = $client;
    }

    /**
     * @param Order $ourOrder
     * @return EntityList
     * @throws \Throwable
     */
    public function collectOrderPositions(Order $ourOrder): EntityList
    {
        $orderPositions = [];
        $itemsMustBeReserved = $this->isItemsMustBeReserved($ourOrder);
        $orderPositions = $this->collectOrderItems($ourOrder, $orderPositions, $itemsMustBeReserved);
        $orderPositions = $this->collectBonuses($ourOrder, $orderPositions, $itemsMustBeReserved);

        return new EntityList($this->client, $orderPositions);
    }

    /**
     * @param Order $ourOrder
     * @param array $positions
     * @return array
     * @throws \Throwable
     */
    protected function collectOrderItems(Order $ourOrder, array $positions, bool $itemsMustBeReserved): array
    {
        $ourOrder->orderItems->each(function (OrderItem $orderItem) use (&$positions, $itemsMustBeReserved) {
            $uuid = $orderItem->variant->getUuid();
            $remoteVariant = Variant::query($this->client)->byId($uuid);
            if ($remoteVariant) {
                $remoteVariant->quantity = $orderItem->quantity;
                $remoteVariant->reserve = $itemsMustBeReserved ? $orderItem->quantity : 0;
                $remoteVariant->price = (round($orderItem->discounted_price / $orderItem->quantity, 2) * 100);
                $positions[] = $remoteVariant;
            }
        });

        return $positions;
    }

    /**
     * @param Order $ourOrder
     * @param array $positions
     * @return array
     * @throws \Throwable
     */
    private function collectBonuses(Order $ourOrder, array $positions, bool $itemsMustBeReserved): array
    {
        $ourOrder->allBonuses->each(function (Bonus $bonus) use (&$positions, $itemsMustBeReserved) {
            $uuid = $bonus->getUuid();
            $remoteBonus = Product::query($this->client)->byId($uuid);
            if ($remoteBonus) {
                $remoteBonus->quantity = (int)$bonus->quantity;
                $remoteBonus->reserve = $itemsMustBeReserved ? (int)$bonus->quantity : 0;
                $positions[] = $remoteBonus;
            }
        });

        return $positions;
    }


    /**
     * @param Order $order
     * @return bool
     */
    private function isItemsMustBeReserved(Order $order): bool
    {
        return in_array($order->status->code, Status::getReserveCodes());
    }
}