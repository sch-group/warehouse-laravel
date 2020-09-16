<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Folders\ProductFolder;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;

class ProductsSynchronizer extends AbstractEntitySynchronizer
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
     * ProductsSynchronizer constructor.
     * @param MoySklad $client
     * @param WarehouseEntityRepository $warehouseEntityRepository
     */
    public function __construct(MoySklad $client, WarehouseEntityRepository $warehouseEntityRepository)
    {
        $this->client = $client;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
    }

    /**
     * @throws \Exception
     */
    public function synchronize(): void
    {
        $this->applyExistedUuidsToOurEntity();

        $this->addOurEntityToRemoteWarehouse();
    }

    /**
     * @throws \Exception
     */
    protected function applyExistedUuidsToOurEntity(): void
    {
        $ourNotMappedProducts = $this->warehouseEntityRepository->getNotMapped()->keyBy('id');
        $existedRemoteProducts = $this->findExistedRemoteEntities(Product::class, $ourNotMappedProducts);
        $this->applyUuidsToOurEntity($existedRemoteProducts, $ourNotMappedProducts);
    }

    /**
     * @throws \Exception
     */
    protected function addOurEntityToRemoteWarehouse(): void
    {
        $ourProducts = $this->warehouseEntityRepository->getNotMapped(['brand'])->keyBy('id');
        $ourProducts->chunk(self::CHUNKS_SIZE)->each(function ($ourProducts) {
            $this->createRemoteProducts($ourProducts);
        });
    }

    /**
     * @param Collection $ourProducts
     * @return EntityList
     * @throws \Exception
     */
    private function createRemoteProducts(Collection $ourProducts): void
    {
        $remoteProducts = $this->prepareRemoteProductsForCreate($ourProducts);
        $remoteBrands = ProductFolder::query($this->client)->getList();

        $createdRemoteProducts = (new EntityList($this->client, $remoteProducts))
            ->each($this->addRemoteBrandRelationToProduct($remoteBrands))
            ->massCreate();

        $this->applyUuidsToOurEntity($createdRemoteProducts, $ourProducts);
    }

    /**
     * @param Collection $ourProducts
     * @return array
     */
    private function prepareRemoteProductsForCreate(Collection $ourProducts): array
    {
        $remoteProducts = [];

        foreach ($ourProducts as $ourProduct) {
            /** @var \App\Models\Products\Product $ourProduct */
            $remoteProducts[] = (new Product($this->client, [
                "name" => $ourProduct->name,
                "code" => (string)$ourProduct->id,
                "parent_uuid" => $ourProduct->brand->getUuid(),
            ]));
        }

        return $remoteProducts;
    }

    /**
     * @param EntityList $remoteBrands
     * @return \Closure
     */
    private function addRemoteBrandRelationToProduct(EntityList $remoteBrands): \Closure
    {
        return function (Product $remoteProduct) use ($remoteBrands) {
            foreach ($remoteBrands as $brand) {
                if ($remoteProduct->parent_uuid == $brand->id) {
                    $remoteProduct
                        ->buildCreation()
                        ->addProductFolder($brand);
                }
            }
        };
    }
}