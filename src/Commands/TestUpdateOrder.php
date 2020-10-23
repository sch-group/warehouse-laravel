<?php


namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use App\Repositories\Order\OrderRepository;
use SchGroup\MyWarehouse\Synchonizers\Helpers\OrderModifier;


class TestUpdateOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:update_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновлет тестовый заказ';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = app(OrderRepository::class);
        $order = $orderRepository->getById(31576);
        /** @var OrderModifier $warehouseOrderModifier */
        $warehouseOrderModifier = app(OrderModifier::class);
        $warehouseOrderModifier->updateOrderInMyWarehouse($order);
    }
}