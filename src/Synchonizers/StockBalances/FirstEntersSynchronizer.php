<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances;

use MoySklad\Components\Specs\LinkingSpecs;
use MoySklad\Entities\Documents\Movements\Enter;
use MoySklad\Entities\Documents\Positions\EnterPosition;
use MoySklad\Entities\Organization;
use MoySklad\Entities\Products\Product;
use MoySklad\Entities\Products\Variant;
use MoySklad\Entities\Store;
use MoySklad\Lists\EntityList;
use MoySklad\MoySklad;
use SchGroup\MyWarehouse\Repositories\VariantWarehouseRepository;

class FirstEntersSynchronizer
{
    const CHUNK_SIZE = 100;
    /**
     * @var MoySklad
     */
    private $client;
    /**
     * @var VariantWarehouseRepository
     */
    private $warehouseRepository;

    public function __construct(MoySklad $client, VariantWarehouseRepository $warehouseRepository)
    {
        $this->client = $client;
        $this->warehouseRepository = $warehouseRepository;
    }

    public function addEntersOfVariantsAvailableQuantity()
    {

        $variantId = "010b5529-f830-11ea-0a80-05210012e7ae";
        $productId = "f4437f99-f800-11ea-0a80-0521000b7be8";
        $organizationId = config('my_warehouse.organization_uuid');
        $storeId = config('my_warehouse.store_uuid');
//        $remoteVariant = Variant::query($this->client)->byId($variantId);
//        $remoteProduct = Product::query($this->client)->byId($productId);
//        $organization = Organization::query($this->client)->byId($organizationId);

//        $store = Store::query($this->client)->byId($storeId);
//
//        $remoteProduct->quantity = 2;
//        $remoteVariant->quantity = 3;
//
//        $enterPositions = new EntityList($this->client, [$remoteVariant]);
//
//        $enter = new Enter($this->client, [
//            "name" => (string)rand(),
//        ]);
//
//        $enter = $enter->buildCreation()
//            ->addOrganization($organization)
//            ->addStore($store)
//            ->addPositionList($enterPositions)
//            ->execute();
//        ;
        $enter = new Enter($this->client, [
            "name" => (string)rand(),
        ]);
//        $enter = $enter->buildCreation()
//            ->addOrganization($organization)
//            ->addStore($store)
//            ->addPositionList($enterPositions)
//            ->execute();
//        dd($enter);

        $mappedVariants = $this->warehouseRepository->getMapped(['morphMyWarehouse'])->keyBy('morphMyWarehouse.uuid');
        dd($mappedVariants);


    }

}