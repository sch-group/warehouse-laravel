<?php


namespace SchGroup\MyWarehouse\Contracts;


use Illuminate\Support\Collection;

interface WarehouseEntityRepository
{
    public function getNotMapped(array $with = []): Collection;
}