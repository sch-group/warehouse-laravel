<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\ShipmentHandlers;

use MoySklad\MoySklad;
use MoySklad\Components\FilterQuery;
use MoySklad\Entities\Documents\Movements\Demand;
use SchGroup\MyWarehouse\Loggers\OrderChangedLogger;
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
     * @var OrderChangedLogger
     */
    protected $logger;

    /**
     * @throws \MoySklad\Exceptions\EntityHasNoIdException
     */
    public function handle(): void
    {
       Demand::query($this->client)->filter((new FilterQuery())
                ->eq("name", $this->remoteOrder->name)
        )->each(function (Demand $demand) {
            $this->logger->info("Order demand deleted for: {$this->remoteOrder->code}");
            $demand->delete();
        });
    }
}