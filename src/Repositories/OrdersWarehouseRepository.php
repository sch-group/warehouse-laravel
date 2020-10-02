<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Orders\Order;
use Illuminate\Support\Collection;

class OrdersWarehouseRepository extends DbWarehouseEntityRepository
{
    /**
     * OrdersWarehouseRepository constructor.
     * @param Order $model
     */
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    /**
     *
     */
    public function getReservedNotShippedOrders(): Collection
    {
        return $this->model
            ->getForStorageReserve()
            ->hasNotMappedYet()
            ->get();
    }
}