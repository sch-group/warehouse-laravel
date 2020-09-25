<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Brands\Brand;

class BrandWarehouseRepository extends DbWarehouseEntityRepository
{
    public function __construct(Brand $model)
    {
        parent::__construct($model);
    }
}