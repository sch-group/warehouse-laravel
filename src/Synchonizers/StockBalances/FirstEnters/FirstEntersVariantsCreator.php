<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\FirstEnters;

use MoySklad\MoySklad;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Documents\Movements\Enter;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use SchGroup\MyWarehouse\Contracts\FirstEntersCreator;
use SchGroup\MyWarehouse\Synchonizers\Helpers\EnterMaker;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Repositories\VariantWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Entities\Linkers\VariantLinker;

/**
 * Загружает текущее состояние склада available_quantity + storage_reserve через оприходвания в моем складе
 * Class FirstEntersSynchronizer
 * @package SchGroup\MyWarehouse\Synchonizers\StockBalances
 */
class FirstEntersVariantsCreator implements FirstEntersCreator
{
    const MAX_ENTER_SIZE = 100;
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var EnterMaker
     */
    private $enterMaker;

    /**
     * @var VariantLinker
     */
    private $variantLinker;
    /**
     * @var StoreDataKeeper
     */
    private $storeDataKeeper;
    /**
     * @var VariantWarehouseRepository
     */
    private $warehouseRepository;

    /**
     * FirstEntersSynchronizer constructor.
     * @param MoySklad $client
     * @param EnterMaker $enterMaker
     * @param StoreDataKeeper $storeDataKeeper
     * @param VariantWarehouseRepository $warehouseRepository
     */
    public function __construct(
        MoySklad $client,
        EnterMaker $enterMaker,
        StoreDataKeeper $storeDataKeeper,
        VariantWarehouseRepository $warehouseRepository)
    {
        $this->client = $client;
        $this->enterMaker = $enterMaker;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->warehouseRepository = $warehouseRepository;
        $this->variantLinker = app(config('my_warehouse.variant_linker_class'));
    }

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    public function createFirstStockBalances(): void
    {
        $this->deleteOldEnters();
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $ourVariants = $this->warehouseRepository->getMapped(['morphMyWarehouse'])->keyBy('morphMyWarehouse.uuid');
        $reservedQuantities = $this->warehouseRepository->storageReserveQuantities();
        $sizeOfVariants = $ourVariants->count();
        $chunkCounter = 0;
        while ($chunkCounter <= $sizeOfVariants) {
            $chunkedRemotedVariants = $this->chunkRemoteVariants($chunkCounter);
            $enterPositions = $this->buildEnterPositions($chunkedRemotedVariants, $ourVariants, $reservedQuantities);
            $this->enterMaker->addNewEnter($organization, $store, $enterPositions);
            $chunkCounter += self::MAX_ENTER_SIZE;
        }
    }

    /**
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     */
    private function deleteOldEnters(): void
    {
        Enter::query($this->client)
            ->filter((new FilterQuery())->neq("name", FirstEntersBonusesCreator::BONUSES_ENTER_NAME))
            ->each(function (Enter $enter) {
                $enter->delete();
            });
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
     * @param Collection $reservedQuantities
     * @return EntityList
     */
    private function buildEnterPositions(
        EntityList $chunkedRemotedVariants,
        Collection $ourVariants,
        Collection $reservedQuantities
    ): EntityList
    {
        $enterPositions = new EntityList($this->client);
        $chunkedRemotedVariants->each(function (Variant $remoteVariant) use ($ourVariants, $enterPositions, $reservedQuantities) {
            if (!empty($ourVariants[$remoteVariant->id])) {
                /** @var \App\Models\Products\Variant $ourVariant */
                $ourVariant = $ourVariants[$remoteVariant->id];
                $stockQuantity = $this->calculateStockQuantity($reservedQuantities, $ourVariant);
                if ($stockQuantity > 0) {
                    $remoteVariant->quantity = $stockQuantity;
                    $remoteVariant->price = $this->variantLinker->defineBuyPrice($ourVariant)['value'];
                    $enterPositions->push($remoteVariant);
                }
            }
        });

        return $enterPositions;
    }

    /**
     * @param Collection $reservedQuantities
     * @param \App\Models\Products\Variant $ourVariant
     * @return Collection|int
     */
    private function calculateStockQuantity(Collection $reservedQuantities, \App\Models\Products\Variant $ourVariant): int
    {
        $reservedQuantity = $reservedQuantities[$ourVariant->id] ?? 0;

        return $ourVariant->available_quantity + $reservedQuantity;
    }
}