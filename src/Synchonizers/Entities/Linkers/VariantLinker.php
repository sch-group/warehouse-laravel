<?php

namespace SchGroup\MyWarehouse\Synchonizers\Entities\Linkers;

use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Products\Variant;

interface VariantLinker
{
    public function defineBuyPrice(\App\Models\Products\Variant $variant): array;

    public function defineSalePrices(\App\Models\Products\Variant $variant): array;

    public function linkRemoteVariantToProduct(Variant $remoteVariant, Product $remoteProduct): void;
}