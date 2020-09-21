<?php

namespace SchGroup\MyWarehouse\Repositories;

use App\Repositories\DbRepository;
use Illuminate\Support\Collection;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;

class DbWarehouseEntityRepository extends DbRepository implements WarehouseEntityRepository
{
    /**
     * @param array $with
     */
    public function getNotMapped(array $with = []): Collection
    {
        return $this->model
            ->with($with)
            ->hasNotMappedYet()
            ->get();
    }

    /**
     * @param array $with
     * @return Collection
     */
    public function getMapped(array $with = []): Collection
    {
        return $this->model
            ->with($with)
            ->alreadyMapped()
            ->get();
    }
}