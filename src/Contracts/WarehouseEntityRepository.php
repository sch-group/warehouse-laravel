<?php


namespace SchGroup\MyWarehouse\Contracts;


interface WarehouseEntityRepository
{
    public function getNotMapped(array $with = []);
}