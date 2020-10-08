<?php


namespace SchGroup\MyWarehouse\Commands;

use Illuminate\Console\Command;
use App\Repositories\Order\OrderRepository;
use SchGroup\MyWarehouse\Jobs\CreateOrderInMyWarehouseJob;


class TestCreateOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:create_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавляет тестовый заказ';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        /** @var OrderRepository $orderRepository */
        $orderRepository = app(OrderRepository::class);
        CreateOrderInMyWarehouseJob::dispatch($orderRepository->getRandomOrder());
    }
}