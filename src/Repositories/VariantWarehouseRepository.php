<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Products\Variant;

class VariantWarehouseRepository extends DbWarehouseEntityRepository
{
    public function __construct(Variant $model)
    {
        parent::__construct($model);
    }
}