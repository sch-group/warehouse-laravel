<?php


namespace SchGroup\MyWarehouse;;

use MoySklad\MoySklad;
use Illuminate\Support\ServiceProvider;
use SchGroup\MyWarehouse\Commands\AddFirstStockBalances;
use SchGroup\MyWarehouse\Commands\AddReservedOrders;
use SchGroup\MyWarehouse\Commands\SyncEntities;
use SchGroup\MyWarehouse\Commands\SyncVariantPrices;

class MyWarehouseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(MoySklad::class, function ($app, $params) {
            $config = config('my_warehouse');
            return MoySklad::getInstance($config['login'], $config['password']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->addCommand();
            $this->addMigrations();
        }

        $this->addConfigFile();
    }

    /**
     *
     */
    private function addCommand(): void
    {
        $this->commands([
            SyncEntities::class,
            SyncVariantPrices::class,
            AddFirstStockBalances::class,
            AddReservedOrders::class,
        ]);
    }

    /**
     *
     */
    private function addMigrations(): void
    {
        if (!class_exists('CreateMyWarehouseEntities')) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_my_warehouse_entities.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_my_warehouse_entities.php'),
                // you can add any number of migrations here
            ], 'migrations');
        }
    }

    /**
     *
     */
    protected function addConfigFile(): void
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('my_warehouse.php'),
        ], 'config');
    }
}