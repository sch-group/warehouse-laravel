<?php


namespace SchGroup\MyWarehouse\Commands;

use App\Models\Orders\Order;
use Illuminate\Console\Command;
use SchGroup\MyWarehouse\Jobs\CreateOrderInMyWarehouseJob;
use SchGroup\MyWarehouse\Jobs\UpdateOrderInMyWarehouseJob;


class UpdateAllReservedOrders extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my-warehouse:update_reserved_orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет все заказы которые в резерве';

    /**
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function handle()
    {
        if(!isMyWarehouseProd()) {
            return;
        }
        $reservedOrders = Order::query()->getForStorageReserve()->get();
        $reservedOrders->each(function (Order $order) {
            if(!empty($order->getUuid())) {
                UpdateOrderInMyWarehouseJob::dispatch($order);
                return;
            }
            CreateOrderInMyWarehouseJob::dispatch($order);
        });
    }
}