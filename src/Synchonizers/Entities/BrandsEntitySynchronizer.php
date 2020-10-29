<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;

use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Folders\ProductFolder;
use SchGroup\MyWarehouse\Loggers\EntitySynchronizeLogger;
use SchGroup\MyWarehouse\Repositories\BrandWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;

class BrandsEntitySynchronizer extends AbstractEntitySynchronizer
{
    const CHUNKS_SIZE = 50;

    use WarehouseEntityHelper;

    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var BrandWarehouseRepository
     */
    private $warehouseEntityRepository;
    /**
     * @var EntitySynchronizeLogger
     */
    private $logger;

    /**
     * BrandsSynchronizer constructor.
     * @param MoySklad $client
     * @param EntitySynchronizeLogger $logger
     * @param BrandWarehouseRepository $warehouseEntityRepository
     */
    public function __construct(MoySklad $client, EntitySynchronizeLogger $logger, BrandWarehouseRepository $warehouseEntityRepository)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
    }

    /**
     * @throws \Exception
     */
    protected function applyExistedUuidsToOurEntity(): void
    {
        $ourBrands = $this->warehouseEntityRepository->getNotMapped()->keyBy('code');

        $existedRemoteBrands = $this->findExistedRemoteEntities(ProductFolder::class, $ourBrands);

        $this->applyUuidsToOurEntity($existedRemoteBrands, $ourBrands);

    }

    /**
     *
     */
    protected function addOurEntityToRemoteWarehouse(): void
    {
        $ourNotMappedBrands = $this->warehouseEntityRepository->getNotMapped()->keyBy('code');

        $ourNotMappedBrands->chunk(self::CHUNKS_SIZE)->each(function ($ourBrands) {
            $this->createRemoteBrands($ourBrands);
        });

    }

    /**
     * @param Collection $ourBrands
     * @return void
     */
    private function createRemoteBrands(Collection $ourBrands): void
    {
        $remoteBrands = [];
        foreach ($ourBrands as $ourBrand) {
            $remoteBrands[] = (new ProductFolder($this->client, [
                "name" => $ourBrand->title,
                "code" => $ourBrand->code,
            ]));
        }

        $createdRemoteBrands = (new EntityList($this->client, $remoteBrands))->massCreate();
        $this->applyUuidsToOurEntity($createdRemoteBrands, $ourBrands);

        $this->logger->info("Brands created: " . $createdRemoteBrands->toJson(0));
    }
}