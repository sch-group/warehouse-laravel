<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Bonuses\Bonus;
use Illuminate\Support\Collection;
use App\Repositories\Bonus\BonusRepository;

class BonusWarehouseRepository extends DbWarehouseEntityRepository
{
    /**
     * @var BonusRepository
     */
    private $bonusRepository;
    /**
     * BonusWarehouseRepository constructor.
     * @param Bonus $model
     */
    public function __construct(Bonus $model)
    {
        parent::__construct($model);
        $this->bonusRepository = app(BonusRepository::class);
    }

    /**
     * @return Collection
     */
    public function storageReserveQuantities(): Collection
    {
        return $this->bonusRepository->storageReserveQuantities();
    }
}