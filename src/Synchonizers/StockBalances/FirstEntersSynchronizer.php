<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances;

use MoySklad\MoySklad;
use MoySklad\Entities\Store;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Organization;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Documents\Movements\Enter;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use SchGroup\MyWarehouse\Repositories\VariantWarehouseRepository;

/**
 * Загружает текущее состояние склада available_quantity через оприходвания в моем складе
 * Class FirstEntersSynchronizer
 * @package SchGroup\MyWarehouse\Synchonizers\StockBalances
 */
class FirstEntersSynchronizer
{
    const MAX_ENTER_SIZE = 100;
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var VariantWarehouseRepository
     */
    private $warehouseRepository;

    /**
     * FirstEntersSynchronizer constructor.
     * @param MoySklad $client
     * @param VariantWarehouseRepository $warehouseRepository
     */
    public function __construct(MoySklad $client, VariantWarehouseRepository $warehouseRepository)
    {
        $this->client = $client;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    public function createStockBalancesByVariantsEnters(): void
    {
        $this->deleteOldEnters();
        list($store, $organization) = $this->defineStoreAndOrganization();
        $ourVariants = $this->loadOurVariants();
        $sizeOfVariants = $ourVariants->count();
        $chunkCounter = 0;
        while ($chunkCounter < $sizeOfVariants) {
            $chunkedRemotedVariants = $this->chunkRemoteVariants($chunkCounter);
            $enterPositions = $this->buildEnterPositions($chunkedRemotedVariants, $ourVariants);
            $this->addNewEnter($organization, $store, $enterPositions);
            $chunkCounter += self::MAX_ENTER_SIZE;
        }
    }

    /**
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     */
    private function deleteOldEnters(): void
    {
        Enter::query($this->client)->getList()->each(function (Enter $enter) {
            $enter->delete();
        });
    }

    /**
     * @return array
     * @throws \Throwable
     */
    private function defineStoreAndOrganization(): array
    {
        $organizationId = config('my_warehouse.organization_uuid');
        $storeId = config('my_warehouse.store_uuid');
        $store = Store::query($this->client)->byId($storeId);
        $organization = Organization::query($this->client)->byId($organizationId);

        return [$store, $organization];
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function defineEnterName(): string
    {
        return (new \DateTime('now'))->format('Y-m-d H:i:s') . "_" . hash('md5', rand());
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function loadOurVariants(): Collection
    {
        return $this->warehouseRepository
            ->getMapped(['morphMyWarehouse'])
            ->where('available_quantity', '>', 0)
            ->keyBy('morphMyWarehouse.uuid');
    }

    /**
     * @param int $chunkCounter
     * @return EntityList
     * @throws \Exception
     */
    private function chunkRemoteVariants(int $chunkCounter): EntityList
    {
        return Variant::query($this->client, QuerySpecs::create([
            "offset" => $chunkCounter,
            "maxResults" => self::MAX_ENTER_SIZE,
        ]))->getList();
    }

    /**
     * @param EntityList $chunkedRemotedVariants
     * @param Collection $ourVariants
     * @return EntityList
     */
    private function buildEnterPositions(EntityList $chunkedRemotedVariants, Collection $ourVariants): EntityList
    {
        $enterPositions = new EntityList($this->client);
        $chunkedRemotedVariants->each(function (Variant $remoteVariant) use ($ourVariants, $enterPositions) {
            $this->collectEnterPositions($ourVariants, $remoteVariant, $enterPositions);
        });

        return $enterPositions;
    }

    /**
     * @param Collection $ourVariants
     * @param Variant $remoteVariant
     * @param EntityList $enterPositions
     */
    private function collectEnterPositions(Collection $ourVariants, Variant $remoteVariant, EntityList $enterPositions): void
    {
        if (!empty($ourVariants[$remoteVariant->id])) {
            /** @var \App\Models\Products\Variant $ourVariant */
            $ourVariant = $ourVariants[$remoteVariant->id];
            $remoteVariant->quantity = $ourVariant->available_quantity;
            $remoteVariant->price = $ourVariant->average_purchase_price * 100;
            $enterPositions->push($remoteVariant);
        }
    }

    /**
     * @param $organization
     * @param $store
     * @param EntityList $enterPositions
     * @return mixed
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     */
    private function addNewEnter($organization, $store, EntityList $enterPositions): void
    {
        $enter = new Enter($this->client, [
            "name" => $this->defineEnterName(),
        ]);

        $enter
            ->buildCreation()
            ->addOrganization($organization)
            ->addStore($store)
            ->addPositionList($enterPositions)
            ->execute();
    }

}