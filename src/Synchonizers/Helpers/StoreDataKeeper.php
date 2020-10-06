<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;


use MoySklad\MoySklad;
use MoySklad\Entities\Store;
use MoySklad\Entities\Organization;
use MoySklad\Entities\AbstractEntity;

class StoreDataKeeper
{
    /**
     * @var MoySklad
     */
    private $client;

    /**
     * StoreDataKeeper constructor.
     * @param MoySklad $client
     */
    public function __construct(MoySklad $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     * @throws \Throwable
     */
    public function defineStore(): AbstractEntity
    {
        $storeId = config('my_warehouse.store_uuid');

        return Store::query($this->client)->byId($storeId);
    }
    /**
     * @return AbstractEntity
     * @throws \Throwable
     */
    public function defineOrganization(): AbstractEntity
    {
        $organizationId = config('my_warehouse.organization_uuid');

        return Organization::query($this->client)->byId($organizationId);
    }
}