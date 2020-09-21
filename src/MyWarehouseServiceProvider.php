<?php


namespace SchGroup\MyWarehouse;

use MoySklad\MoySklad;
use App\Models\Brands\Brand;
use App\Models\Products\Product;
use App\Models\Products\Variant;
use Illuminate\Support\ServiceProvider;
use SchGroup\MyWarehouse\Commands\SyncEntities;
use SchGroup\MyWarehouse\Commands\SyncVariantPrices;
use SchGroup\MyWarehouse\Contracts\WarehouseEntityRepository;
use SchGroup\MyWarehouse\Repositories\DbWarehouseEntityRepository;
use SchGroup\MyWarehouse\Synchonizers\Entities\ProductsSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\VariantsSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Prices\VariantPricesSynchronizer;
use SchGroup\MyWarehouse\Synchonizers\Entities\BrandsEntitySynchronizer;

class MyWarehouseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(MoySklad::class, function ($app, $params) {
            $config = config('my_warehouse');
            return MoySklad::getInstance($config['login'], $config['password']);
        });

        $this->declareWarehouseRepository();
    }

    /**
     *
     */
    protected function declareWarehouseRepository(): void
    {
        $this->app->when(BrandsEntitySynchronizer::class)
            ->needs(WarehouseEntityRepository::class)
            ->give(function () {
                return new DbWarehouseEntityRepository(new Brand());
            });

        $this->app->when(VariantsSynchronizer::class)
            ->needs(WarehouseEntityRepository::class)
            ->give(function () {
                return new DbWarehouseEntityRepository(new Variant());
            });

        $this->app->when(ProductsSynchronizer::class)
            ->needs(WarehouseEntityRepository::class)
            ->give(function () {
                return new DbWarehouseEntityRepository(new Product());
            });

        $this->app->when(VariantPricesSynchronizer::class)
            ->needs(WarehouseEntityRepository::class)
            ->give(function () {
                return new DbWarehouseEntityRepository(new Variant());
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