<?php

namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use SchGroup\MyWarehouse\Contracts\FirstEntersCreator;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\FirstEnters\FirstEntersBonusesCreator;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\FirstEnters\FirstEntersVariantsCreator;


class AddFirstStockBalances extends Command
{
    const ENTITY_SYNCHRONIZERS = [
        'bonus' => FirstEntersBonusesCreator::class,
        'variant' => FirstEntersVariantsCreator::class,
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:add_first_stock_enters {--entity=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавляет оприходования, т.е формирует остатки (available_quantity+storage) упаковок/бонусов';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        $entityType = $this->option('entity');
        /** @var FirstEntersCreator $firstEntersCreator */
        $firstEntersCreator = app(self::ENTITY_SYNCHRONIZERS[$entityType]);

        $firstEntersCreator->createFirstStockBalances();
    }
}