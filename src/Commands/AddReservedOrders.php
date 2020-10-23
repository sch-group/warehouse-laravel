<?php

namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use SchGroup\MyWarehouse\Synchonizers\StockBalances\ReservedOrdersSynchronizer;


class AddReservedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:add_reserved_orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавляет заказы, находящиеся в резерве в мой склад';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        /** @var ReservedOrdersSynchronizer $reservedOrdersSynchronizer */
        $reservedOrdersSynchronizer = app(ReservedOrdersSynchronizer::class);

        $reservedOrdersSynchronizer->createStorageReservedOrders();
    }
}