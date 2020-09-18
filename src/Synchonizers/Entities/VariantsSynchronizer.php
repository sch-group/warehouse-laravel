<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Products\Product;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;
use SchGroup\MyWarehouse\Synchonizers\Entities\VariantLinkers\VariantLinker;

class VariantsSynchronizer extends AbstractEntitySynchronizer
{

    const CHUNKS_SIZE = 50;

    use WarehouseEntityHelper;

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
     * VariantsSynchronizer constructor.
     * @param MoySklad $client
     * @param WarehouseEntityRepository $warehouseEntityRepository
     */
    public function __construct(MoySklad $client, WarehouseEntityRepository $warehouseEntityRepository)
    {
        $this->client = $client;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @throws \Exception
     */
    protected function applyExistedUuidsToOurEntity(): void
    {
        $ourVariants = $this->warehouseEntityRepository->getNotMapped()->keyBy('id');
        $existedRemoteBrands = $this->findExistedRemoteEntities(Variant::class, $ourVariants);
        $this->applyUuidsToOurEntity($existedRemoteBrands, $ourVariants);
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
     * @return void
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
                    $this->variantLinker->buildRemoteVariantFromOur($variant)
                ));
            }
        }
        return $remoteVariants;
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
                $this->variantLinker->linkRemoteVariantToProduct($remoteVariant, $remoteProduct);
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