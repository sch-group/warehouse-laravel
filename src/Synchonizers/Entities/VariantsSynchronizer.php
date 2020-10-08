<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Products\Product;
use SchGroup\MyWarehouse\Repositories\VariantWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

class VariantsSynchronizer extends AbstractEntitySynchronizer
{

    const CHUNKS_SIZE = 50;

    use WarehouseEntityHelper;

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
     * VariantsSynchronizer constructor.
     * @param MoySklad $client
     * @param VariantWarehouseRepository $warehouseEntityRepository
     */
    public function __construct(MoySklad $client, VariantWarehouseRepository $warehouseEntityRepository)
    {
        $this->client = $client;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @throws \Throwable
     */
    protected function applyExistedUuidsToOurEntity(): void
    {
        $ourVariants = $this->warehouseEntityRepository->getNotMapped()->keyBy('id');
        $existedRemoteVariants = $this->findExistedRemoteEntities(Variant::class, $ourVariants);
        $this->applyUuidsToOurEntity($existedRemoteVariants, $ourVariants);
    }

    /**
     * @throws \Exception
     */
    protected function addOurEntityToRemoteWarehouse(): void
    {
        $ourVariants = $this->warehouseEntityRepository->getNotMapped()->keyBy('id');

        $existedRemoteProducts = $this->findRemoteProducts();

        $ourVariants->chunk(self::CHUNKS_SIZE)->each(function ($ourVariants) use ($existedRemoteProducts) {
            $this->createRemoteVariants($ourVariants, $existedRemoteProducts);
        });
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function findRemoteProducts(): array
    {
        $existedRemoteProducts = [];
        Product::query($this->client)->getList()->each(
            function (Product $remoteProduct) use (&$existedRemoteProducts) {
                $existedRemoteProducts[$remoteProduct->id] = $remoteProduct;
            }
        );

        return $existedRemoteProducts;
    }

    /**
     * @param Collection $ourVariants
     * @param array $existedRemoteProducts
     * @throws \Throwable
     */
    protected function createRemoteVariants(Collection $ourVariants, array $existedRemoteProducts): void
    {
        try {
            $remoteVariants = $this->prepareRemoteVariants($ourVariants);
            if (empty($remoteVariants)) {
                return;
            }

            $createdVariants = (new EntityList($this->client, $remoteVariants))
                ->each($this->addRemoteProductRelationToVariant($existedRemoteProducts))
                ->massCreate();
            $this->applyUuidsToOurEntity($createdVariants, $ourVariants);

        } catch (\Exception $exception) {
            dump($exception->getCode(), $exception->getMessage());
        }
    }

    /**
     * @param Collection $ourVariants
     * @return array
     */
    protected function prepareRemoteVariants(Collection $ourVariants): array
    {
        $remoteVariants = [];

        /** @var \App\Models\Products\Variant $variant */
        foreach ($ourVariants as $variant) {
            if (!empty($variant->product->getUuid())) {
                $remoteVariants[] = (new Variant($this->client,
                    $this->buildRemoteVariantFromOur($variant)
                ));
            }
        }
        return $remoteVariants;
    }

    /**
     * @param \App\Models\Products\Variant $variant
     * @return array
     */
    public function buildRemoteVariantFromOur(\App\Models\Products\Variant $variant): array
    {
        return [
            "name" => $variant->title,
            "code" => (string)$variant->id,
            "parent_uuid" => $variant->product->getUuid(),
            "pack_quantity" => $variant->pack_quantity,
            "extra_pack_quantity" => $variant->title,
            "buyPrice" => $this->variantLinker->defineBuyPrice($variant),
            "salePrices" => $this->variantLinker->defineSalePrices($variant),
        ];
    }

    /**
     * @param array $existedRemoteProducts
     * @return \Closure
     */
    protected function addRemoteProductRelationToVariant(array $existedRemoteProducts): \Closure
    {
        return function (Variant $remoteVariant) use ($existedRemoteProducts) {
            if ($this->isParentProductFoundForVariant($existedRemoteProducts, $remoteVariant)) {
                $remoteProduct = $existedRemoteProducts[$remoteVariant->parent_uuid];
                $this->variantLinker->linkRemoteProductToVariant($remoteVariant, $remoteProduct);
            }
        };
    }

    /**
     * @param array $existedRemoteProducts
     * @param Variant $remoteVariant
     * @return bool
     */
    private function isParentProductFoundForVariant(array $existedRemoteProducts, Variant $remoteVariant): bool
    {
        return !empty($existedRemoteProducts[$remoteVariant->parent_uuid]);
    }

}