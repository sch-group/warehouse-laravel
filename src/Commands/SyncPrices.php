<?php


namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use SchGroup\MyWarehouse\Synchonizers\Prices\PricesSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Prices\VariantPricesSynchronizer;


class SyncPrices extends Command
{
    const ENTITY_SYNCHRONIZERS = [
        'variant' => VariantPricesSynchronizer::class,
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:synchronize_prices {--entity=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет цены в сущностях в системе Мой склад';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        if(!isMyWarehouseProd()) {
            return;
        }
        $entityType = $this->option('entity');
        /** @var PricesSynchronizer $entitySynchronizer*/
        $entitySynchronizer = app(self::ENTITY_SYNCHRONIZERS[$entityType]);

        $entitySynchronizer->syncPrices();
    }
}