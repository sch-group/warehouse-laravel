<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\FirstEnters;


use MoySklad\MoySklad;
use App\Models\Bonuses\Bonus;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Documents\Movements\Enter;
use SchGroup\MyWarehouse\Contracts\FirstEntersCreator;
use SchGroup\MyWarehouse\Synchonizers\Helpers\EnterMaker;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Repositories\BonusWarehouseRepository;

/**
 * Class FirstEntersBonusesSynchronizer
 * @package SchGroup\MyWarehouse\Synchonizers\StockBalances
 */
class FirstEntersBonusesCreator implements FirstEntersCreator
{

    const BONUSES_ENTER_NAME = "First_bonuses";
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var BonusWarehouseRepository
     */
    private $warehouseRepository;
    /**
     * @var EnterMaker
     */
    private $enterMaker;
    /**
     * @var StoreDataKeeper
     */
    private $storeDataKeeper;

    /**
     * FirstEntersBonusesSynchronizer constructor.
     * @param MoySklad $client
     * @param EnterMaker $enterMaker
     * @param StoreDataKeeper $storeDataKeeper
     * @param BonusWarehouseRepository $warehouseRepository
     */
    public function __construct(
        MoySklad $client,
        EnterMaker $enterMaker,
        StoreDataKeeper $storeDataKeeper,
        BonusWarehouseRepository $warehouseRepository
    )
    {
        $this->client = $client;
        $this->enterMaker = $enterMaker;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->warehouseRepository = $warehouseRepository;
    }

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     * @throws \Throwable
     */
    public function createFirstStockBalances(): void
    {
        $this->deleteBonusesEnter();
        $store = $this->storeDataKeeper->defineStore();
        $organization = $this->storeDataKeeper->defineOrganization();
        $ourBonuses = $this->warehouseRepository->getMapped(['morphMyWarehouse'])->keyBy('morphMyWarehouse.uuid');
        $reserveQuantities = $this->warehouseRepository->storageReserveQuantities();
        $remoteBonuses = $this->findRemoteBonusesAsProducts();
        $enterPositions = $this->buildEnterPositions($remoteBonuses, $ourBonuses, $reserveQuantities);
        $this->enterMaker->addNewEnter($organization, $store, $enterPositions, self::BONUSES_ENTER_NAME);
    }

    /**
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     */
    private function deleteBonusesEnter(): void
    {
        Enter::query($this->client)
            ->filter((new FilterQuery())->eq("name", self::BONUSES_ENTER_NAME))
            ->each(function (Enter $enter) {
                $enter->delete();
            });
    }

    /**
     * @return EntityList
     * @throws \Exception
     */
    public function findRemoteBonusesAsProducts(): EntityList
    {
        return Product::query($this->client)->filter((new FilterQuery())->like("code", "bonus"));
    }

    /**
     * @param EntityList $chunkedRemotedVariants
     * @param Collection $ourBonuses
     * @param Collection $reserveQuantities
     * @return EntityList
     */
    private function buildEnterPositions(
        EntityList $chunkedRemotedVariants,
        Collection $ourBonuses,
        Collection $reserveQuantities
    ): EntityList
    {
        $enterPositions = new EntityList($this->client);
        $chunkedRemotedVariants->each(function (Product $remoteBonus) use ($ourBonuses, $enterPositions, $reserveQuantities) {
            if (!empty($ourBonuses[$remoteBonus->id])) {
                /** @var Bonus $ourBonus */
                $ourBonus = $ourBonuses[$remoteBonus->id];
                $stockQuantity = $this->calculateStockQuantity($reserveQuantities, $ourBonus);
                if ($stockQuantity > 0) {
                    $remoteBonus->quantity = $stockQuantity;
                    $remoteBonus->price = 0;
                    $enterPositions->push($remoteBonus);
                }
            }
        });

        return $enterPositions;
    }

    /**
     * @param Collection $reserveQuantities
     * @param Bonus $ourBonus
     * @return Collection|int
     */
    private function calculateStockQuantity(Collection $reserveQuantities, Bonus $ourBonus): int
    {
        $reserveQuantity = $reserveQuantities[$ourBonus->id] ?? 0;

        return $ourBonus->available_quantity + $reserveQuantity;
    }
}