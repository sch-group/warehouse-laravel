<?php

namespace SchGroup\MyWarehouse\Repositories;

use App\Repositories\DbRepository;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;

class DbWarehouseEntityRepository extends DbRepository implements WarehouseEntityRepository
{
    /**
     * @param array $with
     */
    public function getNotMapped(array $with = [])
    {
        return $this->model
            ->with($with)
            ->hasNotMappedYet()
            ->get();
    }
}