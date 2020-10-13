<?php


namespace SchGroup\MyWarehouse\Synchonizers\Helpers;


use App\Models\Collections\Collection;
use MoySklad\Components\FilterQuery;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Counterparty;
use MoySklad\Entities\Documents\AbstractDocument;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use MoySklad\MoySklad;
use MoySklad\Entities\Store;
use MoySklad\Entities\Organization;
use MoySklad\Entities\AbstractEntity;

class StoreDataKeeper
{
    const DEFAULT_COUNTER_AGENT_NAME = 'Default Supplier';
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
     * @return Store|AbstractEntity
     * @throws \Throwable
     */
    public function defineStore(): Store
    {
        $storeId = config('my_warehouse.store_uuid');

        return Store::query($this->client)->byId($storeId);
    }

    /**
     * @return Organization|AbstractEntity
     * @throws \Throwable
     */
    public function defineOrganization(): Organization
    {
        $organizationId = config('my_warehouse.organization_uuid');

        return Organization::query($this->client)->byId($organizationId);
    }

    /**
     * @return Counterparty|AbstractDocument
     * @throws \MoySklad\Exceptions\EntityCantBeMutatedException
     * @throws \MoySklad\Exceptions\IncompleteCreationFieldsException
     */
    public function defineDummyCounterAgent(): Counterparty
    {
        $counterParty = Counterparty::query($this->client, QuerySpecs::create(["maxResults" => 1]))
            ->filter((new FilterQuery())->eq("name", self::DEFAULT_COUNTER_AGENT_NAME));

        if (!empty($counterParty[0])) {
            return $counterParty[0];
        }

        return (new Counterparty($this->client, [
            'name' => self::DEFAULT_COUNTER_AGENT_NAME,
        ]))->create();
    }

    /**
     * @return array
     * @throws \Throwable
     */
    public function defineOrderStateListKeyedByUuid(): array
    {
        $metaData = CustomerOrder::getMetaData($this->client);
        $orderAvailableStates = $metaData->states;
        $orderStateList = [];
        foreach ($orderAvailableStates as $state) {
            $orderStateList[$state->id] = $state;
        }

        return $orderStateList;
    }
}