<?php


namespace SchGroup\MyWarehouse\Repositories;


use App\Models\Products\Variant;
use Illuminate\Support\Collection;
use App\Repositories\Products\Variant\VariantRepository;

class VariantWarehouseRepository extends DbWarehouseEntityRepository
{
    /**
     * @var VariantRepository
     */
    private $variantRepository;

    /**
     * VariantWarehouseRepository constructor.
     * @param Variant $model
     */
    public function __construct(Variant $model)
    {
        parent::__construct($model);
        $this->variantRepository = app(VariantRepository::class);
    }

    /**
     * Returns collection of variant_id => storage_reserve_quantity
     *
     * @param array $variantIds
     * @return Collection
     */
    public function storageReserveQuantities(array $variantIds = []): Collection
    {
        return $this->variantRepository->storageReserveQuantities($variantIds);
    }
}