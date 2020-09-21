<?php


namespace SchGroup\MyWarehouse\Contracts;


use Illuminate\Support\Collection;

interface WarehouseEntityRepository
{
    /**
     * @param array $with
     * @return Collection
     */
    public function getNotMapped(array $with = []): Collection;

    /**
     * @param array $with
     * @return Collection
     */
    public function getMapped(array $with = []): Collection;
}