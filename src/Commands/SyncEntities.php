<?php


namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use SchGroup\MyWarehouse\Synchonizers\Entities\BonusesSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\ProductsSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\VariantsSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\OrderStatusSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\BrandsEntitySynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\AbstractEntitySynchronizer;

class SyncEntities extends Command
{
    const ENTITY_SYNCHRONIZERS = [
        'brand' => BrandsEntitySynchronizer::class,
        'product' => ProductsSynchronizer::class,
        'variant' => VariantsSynchronizer::class,
        'order_status' => OrderStatusSynchronizer::class,
        'bonus' => BonusesSynchronizer::class,
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:synchronize {--entity=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизирует наши бренды/упаковки/товары/бонусы c системой Мой склад';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        $entityType = $this->option('entity');
        /** @var AbstractEntitySynchronizer $entitySynchronizer*/
        $entitySynchronizer = app(self::ENTITY_SYNCHRONIZERS[$entityType]);

        $entitySynchronizer->synchronize();
    }
}