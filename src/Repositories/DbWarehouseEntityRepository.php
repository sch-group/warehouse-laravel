<?php

namespace SchGroup\MyWarehouse\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;
use SchGroup\MyWarehouse\Models\MyWarehouseEntity;

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

    /**
     *
     */
    public function destroyMapped(): void
    {
        MyWarehouseEntity::where('entity_type', $this->model->getMorphClass())->delete();
    }
}