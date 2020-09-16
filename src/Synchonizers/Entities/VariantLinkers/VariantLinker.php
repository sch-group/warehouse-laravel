<?php

namespace SchGroup\MyWarehouse\Synchonizers\Entities\VariantLinkers;

use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Products\Variant;

interface VariantLinker
{
    public function buildRemoteVariantFromOur(\App\Models\Products\Variant $variant): array;

    public function linkRemoteVariantToProduct(Variant $remoteVariant, Product $remoteProduct): void;
}