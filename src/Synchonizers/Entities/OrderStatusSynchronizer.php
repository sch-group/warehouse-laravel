<?php


namespace SchGroup\MyWarehouse\Synchonizers\Entities;


use MoySklad\Components\Expand;
use MoySklad\Components\FilterQuery;
use MoySklad\Components\Specs\LinkingSpecs;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use MoySklad\Entities\Folders\ProductFolder;
use MoySklad\Entities\Misc\State;
use MoySklad\MoySklad;
use Tests\Cases\MetaDataTest;

class OrderStatusSynchronizer extends AbstractEntitySynchronizer
{
    /**
     * @var MoySklad
     */
    private $client;

    public function __construct(MoySklad $client)
    {

        $this->client = $client;
    }

    protected function applyExistedUuidsToOurEntity(): void
    {
        $remoteStatuses =         $res = $this->client->getClient()->get('entity/customerorder/metadata');
        dd($remoteStatuses);
    }

    protected function addOurEntityToRemoteWarehouse(): void
    {

    }
}