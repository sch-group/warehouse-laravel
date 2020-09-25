<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Products\Product;

class ProductWarehouseRepository extends DbWarehouseEntityRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
}