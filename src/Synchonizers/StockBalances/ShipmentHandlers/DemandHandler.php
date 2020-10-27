<?php

namespace SchGroup\MyWarehouse\Synchonizers\StockBalances\ShipmentHandlers;

use MoySklad\MoySklad;
use SchGroup\MyWarehouse\Loggers\OrderChangedLogger;
use MoySklad\Entities\Documents\Orders\CustomerOrder;

abstract class DemandHandler
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
     * DemandHandler constructor.
     * @param CustomerOrder $remoteOrder
     */
    public function __construct(CustomerOrder $remoteOrder)
    {
        $this->remoteOrder = $remoteOrder;
        $this->client = app(MoySklad::class);
        $this->logger = app(OrderChangedLogger::class);
    }


    abstract public function handle(): void;

}