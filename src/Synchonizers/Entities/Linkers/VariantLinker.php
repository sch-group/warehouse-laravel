<?php

namespace SchGroup\MyWarehouse\Synchonizers\Entities\Linkers;

use App\Models\Orders\OrderItem;
use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Products\Variant;
use App\Models\Warehouse\WarehouseHistoryItem;

interface VariantLinker
{
    /***
     * @param \App\Models\Products\Variant $variant
     * @return array
     */
    public function defineBuyPrice(\App\Models\Products\Variant $variant): array;

    /**
     * @param \App\Models\Products\Variant $variant
     * @return array
     */
    public function defineSalePrices(\App\Models\Products\Variant $variant): array;

    /**
     * @param \App\Models\Products\Variant $variant
     * @return int
     */
    public function defineVatRate(\App\Models\Products\Variant $variant): int;

    /**
     * @param OrderItem $orderItem
     * @return float
     */
    public function defineOrderItemPrice(OrderItem $orderItem): float;

    /**
     * @param WarehouseHistoryItem $historyItem
     * @return float
     */
    public function defineIncomingPrice(WarehouseHistoryItem $historyItem): float;

    /**
     * @param Variant $remoteVariant
     * @param Product $remoteProduct
     */
    public function linkRemoteProductToVariant(Variant $remoteVariant, Product $remoteProduct): void;

}