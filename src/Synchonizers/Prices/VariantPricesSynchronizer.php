<?php

namespace SchGroup\MyWarehouse\Synchonizers\Prices;

use MoySklad\MoySklad;
use Illuminate\Support\Collection;
use MoySklad\Entities\AbstractEntity;
use MoySklad\Entities\Products\Variant;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;
use SchGroup\MyWarehouse\Repositories\VariantWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

class VariantPricesSynchronizer extends PricesSynchronizer
{
    const CHUNK_SIZE = 100;
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var VariantWarehouseRepository
     */
    private $warehouseEntityRepository;

    /**
     * @var VariantLinker
     */
    private $variantLinker;

    /**
     * VariantPricesSynchronizer constructor.
     * @param VariantWarehouseRepository $warehouseEntityRepository
     */
    public function __construct(MoySklad $client, VariantWarehouseRepository $warehouseEntityRepository)
    {
        $this->client = $client;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    public function syncPrices(): void
    {
        $mappedVariants = $this->warehouseEntityRepository->getMapped();
        $remoteVariants = $this->findRemoteVariants();
        $mappedVariants->chunk(self::CHUNK_SIZE)->each(function ($variants) use ($remoteVariants) {
            $this->syncVariantsPrices($variants, $remoteVariants);
        });
    }

    /**
     *  Запрос ->getList() долгий на получении всех упаковок,
     *   но на дистанции дает выигрыш, чем при поиске каждого варианта по uuid в цикле
     * @return array
     * @throws \Exception
     */
    private function findRemoteVariants(): array
    {
        $remoteVariants = [];
        Variant::query($this->client)->getList()->each(function (Variant $variant) use (&$remoteVariants) {
            $remoteVariants[$variant->id] = $variant;
        });

        return $remoteVariants;
    }

    /**
     * @param Collection $variants
     * @param array $remoteVariants
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    private function syncVariantsPrices(Collection $variants, array $remoteVariants): void
    {
        foreach ($variants as $ourVariant) {
            /*** @var \App\Models\Products\Variant $ourVariant */
            if ($this->isRemoteVariantNotfound($ourVariant, $remoteVariants)) {
                continue;
            }
            $remoteVariant = $remoteVariants[$ourVariant->getUuid()];
            $this->syncVariant($ourVariant, $remoteVariant);
        }
    }

    /**
     * @param \App\Models\Products\Variant $ourVariant
     * @param array $remoteVariants
     * @return bool
     */
    private function isRemoteVariantNotfound(\App\Models\Products\Variant $ourVariant, array $remoteVariants): bool
    {
        return empty($remoteVariants[$ourVariant->getUuid()]);
    }

    /**
     * @param \App\Models\Products\Variant $ourVariant
     * @param Variant $remoteVariant
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    public function syncVariant(\App\Models\Products\Variant $ourVariant, Variant $remoteVariant): void
    {
        if ($this->isNeedModify($ourVariant, $remoteVariant)) {
            dump($ourVariant);
            $remoteVariant->buyPrice = $this->variantLinker->defineBuyPrice($ourVariant);
            $remoteVariant->salePrices = $this->variantLinker->defineSalePrices($ourVariant);
            $remoteVariant->buildUpdate()->execute();
        }
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

        if ($this->emptyColumns($remoteVariant, $ourBuyPrice, $salePrice)) {
            return false;
        }
        $remoteBuyPrice = $remoteVariant->buyPrice->value;
        $remoteSalePrice = $remoteVariant->salePrices[0]->value;
        $buyPriceNotEqual = !$this->arePricesEqual($remoteBuyPrice, $ourBuyPrice);
        $salePriceNotEqual = !$this->arePricesEqual($remoteSalePrice, $salePrice);

        return $buyPriceNotEqual || $salePriceNotEqual;
    }

    /**
     * @param AbstractEntity $remoteVariant
     * @param $ourBuyPrice
     * @param $salePrice
     * @return bool
     */
    private function emptyColumns(AbstractEntity $remoteVariant, float $ourBuyPrice, float $salePrice): bool
    {
        return (empty($remoteVariant->buyPrice) || empty($ourBuyPrice)) ||
            (empty($remoteVariant->salePrices) || empty($salePrice));
    }

    /**
     * https://stackoverflow.com/questions/3148937/compare-floats-in-php
     * Comparison for php float
     * @param float $a
     * @param float $b
     * @return bool
     */
    private function arePricesEqual(float $a, float $b): bool
    {
        return abs(($a - $b)) < 0.00001;
    }
}