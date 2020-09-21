<?php

namespace SchGroup\MyWarehouse\Synchonizers\Prices;

use MoySklad\MoySklad;
use Illuminate\Support\Collection;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Entities\Products\Variant;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

class VariantPricesSynchronizer extends PricesSynchronizer
{
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var WarehouseEntityRepository
     */
    private $warehouseEntityRepository;

    /**
     * @var VariantLinker
     */
    private $variantLinker;

    /**
     * VariantPricesSynchronizer constructor.
     * @param WarehouseEntityRepository $warehouseEntityRepository
     */
    public function __construct(MoySklad $client, WarehouseEntityRepository $warehouseEntityRepository)
    {
        $this->client = $client;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     *
     */
    public function syncPrices(): void
    {
        $mappedVariants = $this->warehouseEntityRepository->getMapped();
        $remoteVariants = $this->findRemoteVariants();
        $mappedVariants->chunk(50)->each(function ($variants) use ($remoteVariants) {
            $this->syncVariantsPrices($variants, $remoteVariants);
        });
    }

    /**
     * @param Collection $variants
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    private function syncVariantsPrices(Collection $variants, array $remoteVariants): void
    {
        foreach ($variants as $ourVariant) {
            /*** @var \App\Models\Products\Variant $ourVariant */
            if (empty($remoteVariants[$ourVariant->getUuid()])) {
                continue;
            }
            $remoteVariant = $remoteVariants[$ourVariant->getUuid()];
            dump($remoteVariant);
            if ($this->isNeedModify($ourVariant, $remoteVariant)) {
                $remoteVariant->buyPrice = $this->variantLinker->defineBuyPrice($ourVariant);
                $remoteVariant->salePrices = $this->variantLinker->defineSalePrices($ourVariant);
                $remoteVariant->buildUpdate()->execute();
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function findRemoteVariants(): array
    {
        $remoteVariants = [];
        Variant::query($this->client)->getList()->each(function (Variant $variant) {
            $remoteVariants[$variant->id] = $variant;
        });

        return $remoteVariants;
    }

    /**
     * @param \App\Models\Products\Variant $ourVariant
     * @param AbstractEntity $remoteVariant
     * @return bool
     */
    private function isNeedModify(\App\Models\Products\Variant $ourVariant, AbstractEntity $remoteVariant): bool
    {
        $ourBuyPrice = $this->variantLinker->defineBuyPrice($ourVariant)['value'];
        $salePrice = $this->variantLinker->defineSalePrices($ourVariant)[0]['value'];

        if (empty($remoteVariant->buyPrice) && empty($ourBuyPrice) ||
            empty($remoteVariant->salePrices) && empty($salePrice)
        ) {
            return false;
        }

        return $remoteVariant->buyPrice->value != $ourBuyPrice ||
            $remoteVariant->salePrices[0]->value != $salePrice;
    }
}