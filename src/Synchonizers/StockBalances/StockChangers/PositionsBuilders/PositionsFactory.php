<?php


namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders;


use App\Models\Warehouse\WarehouseHistory;
use SchGroup\MyWarehouse\Contracts\PositionBuilder;
use App\Models\Warehouse\Bonus\WarehouseBonusHistory;

/**
 * Class PositionsFactory
 * @package SchGroup\MyWarehouse\Synchonizers\StockBalances\StockChangers\PositionsBuilders
 */
class PositionsFactory
{
    const INCOMING_BUILDERS = [
        WarehouseHistory::class => IncomingVariantPositions::class,
        WarehouseBonusHistory::class => IncomingBonusPositions::class
    ];

    const FINDING_BUILDERS = [
      WarehouseHistory::class => FindingVariantPositions::class,
      WarehouseBonusHistory::class => FindingBonusPositions::class,
    ];

    const LOSS_BUILDERS = [
        WarehouseHistory::class => LossVariantPositions::class,
        WarehouseBonusHistory::class => LossBonusPositions::class,
    ];
    /**
     * @param WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @return PositionBuilder
     */
    public static function defineIncomingPositionsBuilder($warehouseHistory): PositionBuilder
    {
        return app(self::INCOMING_BUILDERS[get_class($warehouseHistory)]);
    }

    /**
     * @param WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @return PositionBuilder
     */
    public static function defineInventoryPositionsBuilder($warehouseHistory): PositionBuilder
    {
        return app(self::FINDING_BUILDERS[get_class($warehouseHistory)]);
    }

    /**
     * @param WarehouseBonusHistory|WarehouseHistory $warehouseHistory
     * @return PositionBuilder
     */
    public static function defineLossPositionsBuilder($warehouseHistory): PositionBuilder
    {
        return app(self::LOSS_BUILDERS[get_class($warehouseHistory)]);
    }

}