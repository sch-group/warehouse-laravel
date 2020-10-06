<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances;


use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Lists\EntityList;
use App\Models\Orders\OrderItem;
use Illuminate\Support\Collection;
use MoySklad\Entities\Counterparty;
use MoySklad\Entities\Organization;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Entities\Products\Variant;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use SchGroup\MyWarehouse\Repositories\OrdersWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;

class ReservedOrdersSynchronizer
{
    use WarehouseEntityHelper;

    const CHUNK_SIZE = 2;
    /**
     * @var OrdersWarehouseRepository
     */
    private $ordersWarehouseRepository;
    /**
     * @var MoySklad
     */
    private $client;

    /**
     * LastOrdersSynchronizer constructor.
     * @param OrdersWarehouseRepository $ordersWarehouseRepository
     */
    public function __construct(MoySklad $client, OrdersWarehouseRepository $ordersWarehouseRepository)
    {
        $this->client = $client;
        $this->ordersWarehouseRepository = $ordersWarehouseRepository;
    }

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \Throwable
     */
    public function createStorageReservedOrders(): void
    {
        $this->deleteAllMappedOrders();
        $reservedOrders = $this->ordersWarehouseRepository->getReservedNotShippedOrders()->keyBy('id');
        $organization = $this->getOrganization();
        $reservedOrders->chunk(self::CHUNK_SIZE)->each(function (Collection $chunkedOrders) use ($organization) {
            $this->createRemoteOrders($chunkedOrders, $organization);
        });
    }

    /**
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     */
    private function deleteAllMappedOrders(): void
    {
        $this->ordersWarehouseRepository->destroyMapped();
        CustomerOrder::query($this->client)->getList()->each(function (CustomerOrder $customerOrder) {
            $customerOrder->delete();
        });
    }

    /**
     * @param Collection $chunkedOrders
     * @param AbstractEntity $organization
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \Throwable
     */
    private function createRemoteOrders(Collection $chunkedOrders, Organization $organization): void
    {
        $remoteOrders = $this->buildRemoteOrders($chunkedOrders);

        $createdRemoteOrders = (new EntityList($this->client, $remoteOrders))
            ->each(function (CustomerOrder $remoteOrder) use ($organization, $chunkedOrders) {
                /** @var Order $ourOrder */
                $ourOrder = $chunkedOrders[$remoteOrder->code];
                $counterParty = $this->createOrFindCounterParty($ourOrder);
                $orderPositions = $this->defineOrderPositions($ourOrder);
                $positionList = new EntityList($this->client, $orderPositions);
                $remoteOrder
                    ->buildCreation()
                    ->addCounterparty($counterParty)
                    ->addOrganization($organization)
                    ->addPositionList($positionList);
            })->massCreate();

        $this->applyUuidsToOurEntity($createdRemoteOrders, $chunkedOrders);
    }

    /**
     * @param Collection $chunkedOrders
     * @return array
     */
    private function buildRemoteOrders(Collection $chunkedOrders): array
    {
        $remoteOrders = [];
        $chunkedOrders->each(function (Order $order) use (&$remoteOrders) {
            $remoteOrder = new CustomerOrder($this->client, [
                "name" => (string)$order->order_number,
                "code" => (string)$order->id,
            ]);
            $remoteOrders[] = $remoteOrder;
        });

        return $remoteOrders;
    }

    /**
     * @param Order $order
     * @return Counterparty|AbstractEntity
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    private function createOrFindCounterParty(Order $order): Counterparty
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

    /**
     * @return AbstractEntity
     * @throws \Throwable
     */
    private function getOrganization(): AbstractEntity
    {
        $organizationId = config('my_warehouse.organization_uuid');

        return Organization::query($this->client)->byId($organizationId);
    }

}