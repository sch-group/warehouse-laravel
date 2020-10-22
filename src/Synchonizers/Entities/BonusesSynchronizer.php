<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use MoySklad\MoySklad;
use App\Models\Bonuses\Bonus;
use MoySklad\Lists\EntityList;
use Illuminate\Support\Collection;
use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Folders\ProductFolder;
use SchGroup\MyWarehouse\Repositories\BonusWarehouseRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\StoreDataKeeper;
use SchGroup\MyWarehouse\Synchonizers\Helpers\WarehouseEntityHelper;

class BonusesSynchronizer extends AbstractEntitySynchronizer
{
    const CHUNKS_SIZE = 50;

    const BONUS_CODE_PREFIX = 'bonus_';

    use WarehouseEntityHelper;

    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var BonusWarehouseRepository
     */
    private $warehouseEntityRepository;
    /**
     * @var StoreDataKeeper
     */
    private $storeDataKeeper;

    /**
     * ProductsSynchronizer constructor.
     * @param MoySklad $client
     * @param StoreDataKeeper $storeDataKeeper
     * @param BonusWarehouseRepository $warehouseEntityRepository
     */
    public function __construct(
        MoySklad $client,
        StoreDataKeeper $storeDataKeeper,
        BonusWarehouseRepository $warehouseEntityRepository
    )
    {
        $this->client = $client;
        $this->storeDataKeeper = $storeDataKeeper;
        $this->warehouseEntityRepository = $warehouseEntityRepository;
    }

    /**
     * @throws \Exception
     */
    protected function applyExistedUuidsToOurEntity(): void
    {
        $ourNotMappedProducts = $this->findNotMappedBonuses();

        $existedRemoteProducts = $this->findExistedRemoteEntities(Product::class, $ourNotMappedProducts);

        $this->applyUuidsToOurEntity($existedRemoteProducts, $ourNotMappedProducts);
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    protected function addOurEntityToRemoteWarehouse(): void
    {
        $ourBonusesMapped = $this->findNotMappedBonuses();

        $bonusesProductFolder = $this->storeDataKeeper->defineProductFolderForBonuses();

        $ourBonusesMapped->chunk(self::CHUNKS_SIZE)->each(function ($ourBonuses) use ($bonusesProductFolder) {
            $this->createRemoteProductsForBonuses($ourBonuses, $bonusesProductFolder);
        });
    }

    /**
     * @return Collection
     */
    private function findNotMappedBonuses(): Collection
    {
        $ourBonusesMapped = $this->warehouseEntityRepository->getNotMapped()
            ->keyBy(function (Bonus $bonus) {
                return self::BONUS_CODE_PREFIX . $bonus['id'];
            });;
        return $ourBonusesMapped;
    }

    /**
     * @param Collection $ourBonuses
     * @param ProductFolder $bonusFolder
     * @return void
     * @throws \Throwable
     */
    private function createRemoteProductsForBonuses(Collection $ourBonuses, ProductFolder $bonusFolder): void
    {
        $remoteBonuses = $this->prepareRemoteBonusesForCreate($ourBonuses);

        $createdRemoteBonuses = (new EntityList($this->client, $remoteBonuses))
            ->each(function (Product $remoteProduct) use ($remoteBonuses, $bonusFolder) {
                $remoteProduct
                    ->buildCreation()
                    ->addProductFolder($bonusFolder);
            })->massCreate();

        $this->applyUuidsToOurEntity($createdRemoteBonuses, $ourBonuses);
    }

    /**
     * @param Collection $ourBonuses
     * @return array
     */
    private function prepareRemoteBonusesForCreate(Collection $ourBonuses): array
    {
        $remoteBonuses = [];

        foreach ($ourBonuses as $ourBonus) {
            /** @var Bonus $ourBonus */
            $remoteBonuses[] = (new Product($this->client, [
                "name" => $ourBonus->title,
                "code" => "bonus_" . (string)$ourBonus->id,
            ]));
        }

        return $remoteBonuses;
    }
}