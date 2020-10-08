<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances;


use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Organization;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use SchGroup\MyWarehouse\Repositories\OrdersWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseOrderMaker;

class ReservedOrdersSynchronizer
{
    use WarehouseEntityHelper;

    const CHUNK_SIZE = 2;
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var WarehouseOrderMaker
     */
    private $warehouseOrderMaker;
    /**
     * @var OrdersWarehouseRepository
     */
    private $ordersWarehouseRepository;
    /**
     * @var StoreDataKeeper
     */
    private $storeDataKeeper;

    /**
     * LastOrdersSynchronizer constructor.
     * @param MoySklad $client
     * @param OrdersWarehouseRepository $ordersWarehouseRepository
     * @param WarehouseOrderMaker $warehouseOrderMaker
     */
    public function __construct(
        MoySklad $client,
        StoreDataKeeper $storeDataKeeper,
        WarehouseOrderMaker $warehouseOrderMaker,
        OrdersWarehouseRepository $ordersWarehouseRepository
    )
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->warehouseOrderMaker = $warehouseOrderMaker;
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
        $organization = $this->storeDataKeeper->defineOrganization();
        $remoteOrderStates = $this->storeDataKeeper->defineOrderStateListKeyedByUuid();
        $reservedOrders->chunk(self::CHUNK_SIZE)->each(function (Collection $chunkedOrders) use ($organization, $remoteOrderStates) {
            $this->createRemoteOrders($chunkedOrders, $organization, $remoteOrderStates);
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
     * @param Organization $organization
     * @param array $remoteStatuses
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     * @throws \Throwable
     */
    private function createRemoteOrders(Collection $chunkedOrders, Organization $organization, array $remoteStatuses): void
    {
        $remoteOrders = $this->buildRemoteOrders($chunkedOrders);

        $createdRemoteOrders = (new EntityList($this->client, $remoteOrders))
            ->each(function (CustomerOrder $remoteOrder) use ($organization, $chunkedOrders, $remoteStatuses) {
                /** @var Order $ourOrder */
                $ourOrder = $chunkedOrders[$remoteOrder->code];
                $this->warehouseOrderMaker->addRelationsToRemoteOrder($ourOrder, $remoteOrder, $organization, $remoteStatuses);
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
            $remoteOrders[] = $this->warehouseOrderMaker->createInstanceOfRemoteOrder($order);
        });

        return $remoteOrders;
    }
}