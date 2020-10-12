<?php

namespace SchGroup\MyWarehouse\Synchonizers\Helpers\DemandsHandlers;

use MoySklad\MoySklad;
use App\Models\Orders\Order;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\Documents\Movements\Demand;
use MoySklad\Entities\Documents\Orders\CustomerOrder;

class DemandDestroyer extends DemandHandler
{
    /**
     * @var CustomerOrder
     */
    protected $remoteOrder;

    /**
     * @var MoySklad
     */
    protected $client;

    /**
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     */
    public function handle(): void
    {
       Demand::query($this->client)->filter((new FilterQuery())
                ->eq("name", $this->remoteOrder->name)
        )->each(function (Demand $demand) {
            $demand->delete();
        });
    }
}