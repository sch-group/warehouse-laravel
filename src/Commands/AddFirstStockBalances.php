<?php

namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\FirstEntersSynchronizer;


class AddFirstStockBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:add_first_stock_enters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавляет оприходования, т.е формирует остатки (available_quantity) упаковок';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        /** @var FirstEntersSynchronizer $firstEnterSynchronizer */
        $firstEnterSynchronizer = app(FirstEntersSynchronizer::class);

        $firstEnterSynchronizer->addEntersOfVariantsAvailableQuantity();
    }
}