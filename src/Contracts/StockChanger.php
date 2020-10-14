<?php


namespace SchGroup\MyWarehouse\Contracts;


use App\Models\Warehouse\WarehouseHistory;

interface StockChanger
{
    public function createBy(WarehouseHistory $warehouseHistory): void;
}