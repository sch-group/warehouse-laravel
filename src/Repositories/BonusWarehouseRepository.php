<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Bonuses\Bonus;

class BonusWarehouseRepository extends DbWarehouseEntityRepository
{
    /**
     * BonusWarehouseRepository constructor.
     * @param Bonus $model
     */
    public function __construct(Bonus $model)
    {
        parent::__construct($model);
    }
}