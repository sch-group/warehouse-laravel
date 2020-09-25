<?php

namespace SchGroup\MyWarehouse\Repositories;

use App\Repositories\DbRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;

class DbWarehouseEntityRepository implements WarehouseEntityRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * DbWarehouseEntityRepository constructor.
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
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